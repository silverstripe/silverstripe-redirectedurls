<?php

namespace SilverStripe\RedirectedURLs\Tests\Admin;

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
}
