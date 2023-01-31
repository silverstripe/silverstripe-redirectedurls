<?php

namespace SilverStripe\RedirectedURLs\Test;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

class RedirectedURLTest extends SapphireTest
{

    /**
     * @var string
     */
    protected static $fixture_file = 'RedirectedURLTest.yml';

    /**
     * @var RedirectedURL
     */
    protected $model;

    protected function setUp(): void
    {
        $this->model = RedirectedURL::create();
        parent::setUp();
    }

    public function testSetFromQueryString(): void
    {
        $val = '/test/url?subpage=12';

        $this->model->setFrom($val);

        $this->assertEquals('/test/url', $this->model->FromBase);
        $this->assertEquals('subpage=12', $this->model->FromQuerystring);
    }

    public function testSetFrom(): void
    {
        $val = '/test/url';

        $this->model->setFrom($val);

        $this->assertEquals('/test/url', $this->model->FromBase);
        $this->assertEmpty($this->model->FromQuerystring);
    }

    public function testGetFrom(): void
    {
        $val = '/test/url';

        $this->model->setFrom($val);

        $this->assertEquals('/test/url', $this->model->getFrom());

        $this->model->setFrom($val . '?subpage=12');

        $this->assertEquals('/test/url', $this->model->FromBase);
        $this->assertEquals('subpage=12', $this->model->FromQuerystring);

        $this->assertEquals($val . '?subpage=12', $this->model->getFrom());
    }

    public function testSetFromBase(): void
    {
        $val = 'test/url';

        $this->model->setFromBase($val);

        $this->assertEquals('/test/url', $this->model->getFrom());
    }


    public function testFindByFromNoSlash(): void
    {
        // Without preceding slash
        $redirect = $this->model->findByFrom('test/url');

        $this->assertInstanceOf(RedirectedURL::class, $redirect);

        $this->assertEquals('/test/target', $redirect->To);
    }

    public function testFindByFromTrailingQuestionmark(): void
    {
        // With ?
        $redirect = $this->model->findByFrom('/test/url?');

        $this->assertInstanceOf(RedirectedURL::class, $redirect);

        $this->assertEquals('/test/target', $redirect->To);
    }

    public function testFindByFromNormal(): void
    {
        // Same with slash
        $redirect = $this->model->findByFrom('/test/url');
        $this->assertInstanceOf(RedirectedURL::class, $redirect);

        $this->assertEquals('/test/target', $redirect->To);
    }

    public function testFindByFromQueryString(): void
    {
        // Search for subpage
        $redirect = $this->model->findByFrom('/test/url-2?subpage=12');

        $this->assertInstanceOf(RedirectedURL::class, $redirect);

        $this->assertEquals('/test/target-2', $redirect->To);
    }

    public function testFindByFromNoResult(): void
    {
        $redirect = $this->model->findByFrom('/test/no-exists');

        $this->assertNull($redirect);
    }
}
