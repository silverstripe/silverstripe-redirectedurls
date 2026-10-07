<?php

namespace SilverStripe\RedirectedURLs\Tests\Extension;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\RedirectedURLs\Service\RedirectedURLService;
use SilverStripe\RedirectedURLs\Tests\Extension\RedirectedURLHandlerTest\HiddenTargetExtension;

class RedirectedURLHandlerTest extends FunctionalTest
{
    protected static $fixture_file = 'RedirectedURLHandlerTest.yml';

    protected static $required_extensions = [
        RedirectedURL::class => [
            HiddenTargetExtension::class,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->autoFollowRedirection = false;
    }

    public function testHandleURLRedirectionFromBase(): void
    {
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect-signups');
        $response = $this->get('/signups/');

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals(
            Director::absoluteURL($redirect->To),
            $response->getHeader('Location')
        );
    }

    public function testHandleRootRedirectWithExtension(): void
    {
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect-root-extension');
        $response = $this->get($redirect->FromBase);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals(
            Director::absoluteURL($redirect->To),
            $response->getHeader('Location')
        );
    }

    public function testHandleURLRedirectionWithQueryString(): void
    {
        $expected = $this->objFromFixture(RedirectedURL::class, 'redirect-with-query');
        $response = $this->get('query-test-with-query-string?foo=bar');

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals(
            Director::absoluteURL($expected->To),
            $response->getHeader('Location')
        );
    }

    public function testFromBaseFallbackIsDisabledByDefault(): void
    {
        $response = $this->get('fallback-agree');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFromBaseFallbackUsesSharedTarget(): void
    {
        Config::modify()->set(RedirectedURLService::class, 'frombase_fallback', true);
        $expected = $this->objFromFixture(RedirectedURL::class, 'redirect-fallback-agree-print');

        // Without a querystring, and with a querystring that none of the redirects asks for
        foreach (['fallback-agree', 'fallback-agree?unrelated=1'] as $url) {
            $response = $this->get($url);

            $this->assertEquals(301, $response->getStatusCode(), $url);
            $this->assertEquals(
                Director::absoluteURL($expected->To),
                $response->getHeader('Location'),
                $url
            );
        }
    }

    public function testFromBaseFallbackIgnoresDifferentTargets(): void
    {
        Config::modify()->set(RedirectedURLService::class, 'frombase_fallback', true);
        $response = $this->get('fallback-disagree');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFromBaseFallbackIgnoresDifferentRedirectCodes(): void
    {
        Config::modify()->set(RedirectedURLService::class, 'frombase_fallback', true);
        $response = $this->get('fallback-codes');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFromBaseFallbackKeepsExactMatches(): void
    {
        Config::modify()->set(RedirectedURLService::class, 'frombase_fallback', true);

        $expected = [
            'fallback-precedence?print=1' => 'redirect-fallback-precedence-query',
            'fallback-precedence' => 'redirect-fallback-precedence-plain',
            'fallback-disagree?format=pdf' => 'redirect-fallback-disagree-format',
        ];

        foreach ($expected as $url => $fixture) {
            $redirect = $this->objFromFixture(RedirectedURL::class, $fixture);
            $response = $this->get($url);

            $this->assertEquals(301, $response->getStatusCode(), $url);
            $this->assertEquals(
                Director::absoluteURL($redirect->To),
                $response->getHeader('Location'),
                $url
            );
        }
    }

    public function testFromBaseFallbackAppliesExtensionFilter(): void
    {
        Config::modify()->set(RedirectedURLService::class, 'frombase_fallback', true);
        $expected = $this->objFromFixture(RedirectedURL::class, 'redirect-fallback-filtered-print');
        $response = $this->get('fallback-filtered');

        // The redirect that is filtered out doesn't count as a second target
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals(
            Director::absoluteURL($expected->To),
            $response->getHeader('Location')
        );
    }
}
