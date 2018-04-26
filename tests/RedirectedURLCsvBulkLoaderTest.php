<?php

class RedirectedURLCsvBulkLoaderTest extends SapphireTest {

    protected static $fixture_file = 'RedirectedURLCsvBulkLoaderTest.yml';

    public function testDuplicateFromImport() {
        $loader = new RedirectedURLCsvBulkLoader('RedirectedURL');
        $results = $loader->load($this->getCurrentRelativePath() . '/RedirectedURLCsvBulkLoaderTest.csv');
        $created = $results->Created()->toArray();
        $this->assertCount(2, $created);

        $this->assertEquals($created[0]->FromBase, '/about-us/duplicated-from-base.html');
        $this->assertEquals($created[0]->FromQuerystring, 'duplicated-query-string=1');

        $this->assertEquals($created[1]->FromBase, '/example/with-querystring.html');
        $this->assertEquals($created[1]->FromQuerystring, 'foo=1');
    }

}