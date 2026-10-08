<?php

namespace SilverStripe\RedirectedURLs\Tests\Admin;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\RedirectedURLs\Admin\RedirectedURLAdmin;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

class RedirectedURLAdminTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testImportMinimalCsv(): void
    {
        // A UTF-8 BOM (as saved by Excel) followed by only the documented columns, plus a row without a FromBase
        $csv = "\xEF\xBB\xBFFromBase,FromQuerystring,To\n"
            . "/terms.php,,/terms\n"
            . "/ipfind.aspx,,/IPFind.php\n"
            . ",,/no-from\n";

        $path = tempnam(sys_get_temp_dir(), 'redirectedurls');
        file_put_contents($path, $csv);

        $importer = RedirectedURLAdmin::singleton()->getModelImporters()[RedirectedURL::class];
        $importer->load($path);

        unlink($path);

        $redirect = RedirectedURL::get()->find('FromBase', '/terms.php');

        $this->assertNotNull($redirect);
        $this->assertEquals('/terms', $redirect->To);
        $this->assertEquals('External', $redirect->RedirectionType);
        $this->assertNotNull(RedirectedURL::get()->find('FromBase', '/ipfind.aspx'));

        // Importing again should update the existing records rather than duplicating them
        file_put_contents($path, $csv);
        $importer->load($path);
        unlink($path);

        $this->assertCount(1, RedirectedURL::get()->filter('FromBase', '/terms.php'));
    }

    public function testImportLinksToInternalPage(): void
    {
        $page = SiteTree::create(['Title' => 'Contact', 'URLSegment' => 'contact']);
        $page->write();

        $csv = "\"FromBase\",\"FromQuerystring\",\"To\",\"RedirectionType\"\n"
            . "\"directions\",,\"contact\",\"Internal\"\n"
            . "\"old-contact\",,\"/contact?form=1\",\"Internal\"\n"
            . "\"products\",,\"https://example.com\",\"External\"\n";

        $path = tempnam(sys_get_temp_dir(), 'redirectedurls');
        file_put_contents($path, $csv);

        RedirectedURLAdmin::singleton()->getModelImporters()[RedirectedURL::class]->load($path);

        unlink($path);

        $redirect = RedirectedURL::get()->find('FromBase', '/directions');

        $this->assertEquals('Internal', $redirect->RedirectionType);
        $this->assertEquals($page->ID, $redirect->LinkToID);

        // A querystring can't be represented by a page link, so this falls back to External
        $redirect = RedirectedURL::get()->find('FromBase', '/old-contact');

        $this->assertEquals('External', $redirect->RedirectionType);
        $this->assertEquals('/contact?form=1', $redirect->Link());

        $redirect = RedirectedURL::get()->find('FromBase', '/products');

        $this->assertEquals('External', $redirect->RedirectionType);
        $this->assertEquals(0, $redirect->LinkToID);
    }
}
