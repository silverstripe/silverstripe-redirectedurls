<?php

namespace SilverStripe\RedirectedURLs\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\ORM\DataObject;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * Provides CMS Administration of {@link: RedirectedURL} objects
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 */
class RedirectedURLAdmin extends ModelAdmin
{

    /**
     * @var string
     * @config
     */
    private static $url_segment = 'redirects';

    /**
     * @var string
     * @config
     */
    private static $menu_title = 'Redirects';

    /**
     * @var string
     * @config
     */
    private static $menu_icon = 'silverstripe/redirectedurls:images/redirect.svg';

    /**
     * @var array
     * @config
     */
    private static $managed_models = array(
        RedirectedURL::class,
    );

    /**
     * Overridden to add duplicate checking to the bulkloader to prevent
     * multiple records with the same 'FromBase' value.
     *
     * Duplicates are found via callback to {@link: RedirectedURL.findByForm}.
     *
     * @return array Map of model class names to importer instances
     */
    public function getModelImporters()
    {
        $importer = CsvBulkLoader::create(RedirectedURL::class);
        $importer->duplicateChecks = [
            'FromBase' => ['callback' => 'findByFrom'],
        ];
        return [
            RedirectedURL::class => $importer
        ];
    }

    /**
     * Overriden so that the CSV column headings have the exact field names of the DataObject
     *
     * To prevent field name conversion in DataObject::summaryFields() during export
     * e.g. 'FromBase' is output as 'From Base'
     *
     * @return array
     */
    public function getExportFields()
    {
        $fields = array();
        foreach (DataObject::getSchema()->databaseFields($this->modelClass) as $field => $spec) {
            $fields[$field] = $field;
        }
        return $fields;
    }
}
