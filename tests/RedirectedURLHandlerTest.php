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

	public function testHandleMixedCaseURLRedirection() {
		$response = $this->get('Query-test-with-mixed-case-url');
		$expected = $this->objFromFixture('RedirectedURL', 'redirect-with-mixed-case-url');

		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteURL($expected->To),
			$response->getHeader('Location')
		);
	}

	public function testHandleURLRedirectionWithMixedCaseQuery() {
		$response = $this->get('query-test-with-mixed-case-query-string?Foo=bar');
		$expected = $this->objFromFixture('RedirectedURL', 'redirect-with-mixed-case-query');

		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteURL($expected->To),
			$response->getHeader('Location')
		);
	}
}
