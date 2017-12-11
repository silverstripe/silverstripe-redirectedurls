<?php

namespace SilverStripe\RedirectedURLs;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\CsvBulkLoader;

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
     */
    private static $url_segment = 'redirects';

    /**
     * @var string
     */
    private static $menu_title = 'Redirects';

    /**
     * @var string
     */
    private static $menu_icon_class = 'font-icon-switch';

    /**
     * @var array
     */
    private static $managed_models = [
        RedirectedURL::class
    ];

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
        $importer = new CsvBulkLoader("RedirectedURL");
        $importer->duplicateChecks = [
            'FromBase' => [
                'callback' => 'findByFrom'
            ],
        ];

        return [
            'RedirectedURL' => $importer
        ];
    }

    /**
     * Overridden so that the CSV column headings have the exact field names of the DataObject
     *
     * To prevent field name conversion in DataObject::summaryFields() during export
     * e.g. 'FromBase' is output as 'From Base'
     *
     * @return array
     */
    public function getExportFields()
    {
        $fields = [];
        foreach (singleton($this->modelClass)->config()->db as $field => $spec) {
            $fields[$field] = $field;
        }
        return $fields;
    }
}
