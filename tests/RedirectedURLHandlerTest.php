<?php

/**
 * @package redirectedurls
 * @subpackage tests
 */
class RedirectedURLHandlerTest extends FunctionalTest {
	
	static $fixture_file = 'redirectedurls/tests/RedirectedURLHandlerTest.yml';
	
	function setUp() {
		parent::setUp();
		
		$this->autoFollowRedirection = false;
	}
	
	function testHandleURLRedirectionFromBase() {
		$redirect = $this->objFromFixture('RedirectedURL', 'redirect-signups');
		
		$response = $this->get($redirect->FromBase);
		$this->assertEquals(301, $response->getStatusCode());
		
		$this->assertEquals(
			Director::absoluteURL($redirect->To),
			$response->getHeader('Location')
		);
	}
	
	function testHandleURLRedirectionWithQueryString() {
		$response = $this->get('query-test-with-query-string?foo=bar');
		$expected = $this->objFromFixture('RedirectedURL', 'redirect-with-query');
		
		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteURL($expected->To),
			$response->getHeader('Location')
		);
	}
	
	function testArrayToLowercase() {
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