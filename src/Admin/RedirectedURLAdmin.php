<?php

namespace SilverStripe\RedirectedURLs\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\ORM\DataObject;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * Provides CMS Administration of {@link: RedirectedURL} objects
 */
class RedirectedURLAdmin extends ModelAdmin
{

    private static string $url_segment = 'redirects';

    private static string $menu_title = 'Redirects';

    private static string $menu_icon = 'silverstripe/redirectedurls:images/redirect.svg';

    private static array $managed_models = [
        RedirectedURL::class,
    ];

    /**
     * Overridden to add duplicate checking to the bulkloader to prevent
     * multiple records with the same 'FromBase' value.
     *
     * Duplicates are found via callback to {@link: RedirectedURL.findByForm}.
     *
     * @return array Map of model class names to importer instances
     */
    public function getModelImporters(): array
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
     */
    public function getExportFields(): array
    {
        $fields = array();

        foreach (DataObject::getSchema()->databaseFields($this->modelClass) as $field => $spec) {
            $fields[$field] = $field;
        }

        return $fields;
    }
}
