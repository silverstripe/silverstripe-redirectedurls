<?php

namespace SilverStripe\RedirectedURLs\Tests\Extension;

use SilverStripe\Control\Director;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

class RedirectedURLHandlerTest extends FunctionalTest
{
    protected static $fixture_file = 'RedirectedURLHandlerTest.yml';

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

    public function testHandleURLRedirectionWithNonAsciiCharacters(): void
    {
        // Browsers send non-ASCII characters percent-encoded (upper-case hex), and the From base may have been
        // entered either literally or encoded
        $expected = [
            'caf%C3%A9-menu' => 'redirect-literal-character',
            'news/two-diploma%E2%80%99s-awarded' => 'redirect-literal-quote',
            'th%C3%A9-encoded' => 'redirect-encoded-character',
            'men%C3%BC/specials' => 'redirect-literal-wildcard',
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

    public function testHandleURLRedirectionPrefersFromBaseAsRequested(): void
    {
        // When a From base exists both literally and encoded, the one in the form of the request wins
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect-dual-encoded');
        $response = $this->get('dual-%C3%A9');

        $this->assertEquals(
            Director::absoluteURL($redirect->To),
            $response->getHeader('Location')
        );
    }
}
