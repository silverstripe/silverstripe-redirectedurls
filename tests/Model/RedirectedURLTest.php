<?php

namespace SilverStripe\RedirectedURLs\Tests\Model;

use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;
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

    public function testSetFromQuerystring(): void
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

    public function testFindByFromQuerystring(): void
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

    public function testFindByFromEmpty(): void
    {
        $this->assertNull($this->model->findByFrom(''));
        $this->assertNull($this->model->findByFrom(null));
    }

    public function testRedirectionTypeInferredFromTo(): void
    {
        // redirect1 only has a $To value (as legacy data and minimal CSV imports do)
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect1');

        $this->assertEquals('External', $redirect->RedirectionType);
        $this->assertEquals('/test/target', $redirect->Link());

        // An Internal redirect with a linked page is left alone
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect3');

        $this->assertEquals('Internal', $redirect->RedirectionType);
    }

    public function testRequireDefaultRecordsMigratesLegacyRedirects(): void
    {
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect1');

        // Simulate data from before RedirectionType existed, bypassing onBeforeWrite()
        DB::prepared_query(
            'UPDATE "RedirectedURL" SET "RedirectionType" = ? WHERE "ID" = ?',
            ['Internal', $redirect->ID]
        );

        $this->model->requireDefaultRecords();

        $this->assertEquals('External', RedirectedURL::get()->byID($redirect->ID)->RedirectionType);
        $this->assertEquals(
            'Internal',
            $this->objFromFixture(RedirectedURL::class, 'redirect3')->RedirectionType
        );
    }

    public function testLinkTo(): void
    {
        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect3');

        $this->assertEquals('page-1', $redirect->Link());
    }

    public function testLinkToAsset(): void
    {
        $file = $this->objFromFixture(File::class, 'file1');
        $file->setFromLocalFile(dirname(__FILE__) . '/../resources/600x400.png');
        $file->write();
        $file->publishRecursive();

        $redirect = $this->objFromFixture(RedirectedURL::class, 'redirect4');

        $this->assertEquals('/assets/600x400.png', $redirect->Link());
    }
}
