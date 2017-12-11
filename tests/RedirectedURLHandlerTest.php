<?php

namespace SilverStripe\RedirectedURLs\Tests;

use ReflectionMethod;
use SilverStripe\Control\Director;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\RedirectedURLs\RedirectedURL;
use SilverStripe\RedirectedURLs\RedirectedURLHandler;

/**
 * @package redirectedurls
 * @subpackage tests
 */
class RedirectedURLHandlerTest extends FunctionalTest
{

    /**
     * @var string
     */
    protected static $fixture_file = 'tests/RedirectedURLHandlerTest.yml';

    /**
     * Set up for testing.
     */
    public function setUp()
    {
        parent::setUp();

        $this->autoFollowRedirection = false;
    }

    /**
     * Does a root redirect work
     */
    public function testHandleRootRedirectWithExtension()
    {
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect-root-extension');

        $response = $this->get($redirect->FromBase);
        $this->assertEquals(301, $response->getStatusCode());

        $this->assertEquals(
            Director::absoluteURL($redirect->To),
            $response->getHeader('Location')
        );
    }

    /**
     * Does a base redirection work
     */
    public function testHandleURLRedirectionFromBase()
    {
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect-signups');

        $response = $this->get($redirect->FromBase);
        $this->assertEquals(301, $response->getStatusCode());

        $this->assertEquals(
            Director::absoluteURL($redirect->To),
            $response->getHeader('Location')
        );
    }

    /**
     * Does a query string work
     */
    public function testHandleURLRedirectionWithQueryString()
    {
        $response = $this->get('query-test-with-query-string?foo=bar');
        $expected = $this->objFromFixture(RedirectedURL::class, 'redirect-with-query');

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals(
            Director::absoluteURL($expected->To),
            $response->getHeader('Location')
        );
    }

    /**
     * Does arrayToLowercase work
     */
    public function testArrayToLowercase()
    {
        $array = ['Foo' => 'bar', 'baz' => 'QUX'];

        $cont = new RedirectedURLHandler();

        $arrayToLowercaseMethod = new ReflectionMethod(RedirectedURLHandler::class, 'arrayToLowercase');
        $arrayToLowercaseMethod->setAccessible(true);

        $this->assertEquals(
            [
                'foo' => 'bar',
                'baz' => 'qux'
            ],
            $arrayToLowercaseMethod->invoke($cont, $array)
        );
    }
}
