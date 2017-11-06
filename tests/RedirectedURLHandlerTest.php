<?php

/**
 * @package redirectedurls
 * @subpackage tests
 */
class RedirectedURLHandlerTest extends FunctionalTest {

	protected static $fixture_file = 'redirectedurls/tests/RedirectedURLHandlerTest.yml';

	public function setUp() {
		parent::setUp();
		
		$this->autoFollowRedirection = false;
	}

	public function testHanldeRootRedirectWithExtension() {
		$redirect = $this->objFromFixture('RedirectedURL', 'redirect-root-extension');

		$response = $this->get($redirect->FromBase);
		$this->assertEquals(301, $response->getStatusCode());

		$this->assertEquals(
			Director::absoluteURL($redirect->To),
			$response->getHeader('Location')
		);
	}

	public function testHandleURLRedirectionFromBase() {
		$redirect = $this->objFromFixture('RedirectedURL', 'redirect-signups');
		
		$response = $this->get($redirect->FromBase);
		$this->assertEquals(301, $response->getStatusCode());
		
		$this->assertEquals(
			Director::absoluteURL($redirect->To),
			$response->getHeader('Location')
		);
	}

	public function testHandleURLRedirectionWithQueryString() {
		$response = $this->get('query-test-with-query-string?foo=bar');
		$expected = $this->objFromFixture('RedirectedURL', 'redirect-with-query');

		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteURL($expected->To),
			$response->getHeader('Location')
		);
	}

	public function testHandleURLRedirectionWithMixedCaseQueryString() {
		$response = $this->get('query-test-with-query-string?Foo=bar');
		$expected = $this->objFromFixture('RedirectedURL', 'redirect-with-query');

		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteURL($expected->To),
			$response->getHeader('Location')
		);
	}

	public function testArrayToLowercase() {
		$array = array('Foo' => 'bar', 'baz' => 'QUX');
		
		$cont = new RedirectedURLHandler();

		$arrayToLowercaseMethod = new ReflectionMethod('RedirectedURLHandler', 'arrayToLowercase');
		$arrayToLowercaseMethod->setAccessible(true);
		
		$this->assertEquals(
			array('foo'=> 'bar', 'baz' => 'qux'),
			$arrayToLowercaseMethod->invoke($cont, $array)
		);
	}
}
