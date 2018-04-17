<?php

namespace SilverStripe\RedirectedURLs\Test;

use ReflectionMethod;
use SilverStripe\Control\Director;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\RedirectedURLs\Extension\RedirectedURLHandler;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * @package redirectedurls
 * @subpackage tests
 */
class RedirectedURLHandlerTest extends FunctionalTest
{
    protected static $fixture_file = 'RedirectedURLHandlerTest.yml';

    protected function setUp()
    {
        parent::setUp();

        $this->autoFollowRedirection = false;
    }

    public function testHanldeRootRedirectWithExtension()
    {
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect-root-extension');

        $response = $this->get($redirect->FromBase);
        $this->assertEquals(301, $response->getStatusCode());

        $this->assertEquals(
            Director::absoluteURL($redirect->To),
            $response->getHeader('Location')
        );
    }

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

    public function testArrayToLowercase()
    {
        $array = array('Foo' => 'bar', 'baz' => 'QUX');

        $cont = new RedirectedURLHandler();

        $arrayToLowercaseMethod = new ReflectionMethod(RedirectedURLHandler::class, 'arrayToLowercase');
        $arrayToLowercaseMethod->setAccessible(true);

        $this->assertEquals(
            array('foo' => 'bar', 'baz' => 'qux'),
            $arrayToLowercaseMethod->invoke($cont, $array)
        );
    }
}
