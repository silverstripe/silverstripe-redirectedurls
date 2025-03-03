<?php

namespace SilverStripe\RedirectedURLs\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\ORM\DataObject;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\Security\Permission;

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


    public function subsiteCMSShowInMenu()
    {
        return (
            Permission::check('ADMIN') ||
            Permission::check('CMS_ACCESS_SilverStripe\RedirectedURLs\Admin\RedirectedURLAdmin')
        );
    }


    public function getList()
    {
        $list = parent::getList();

        if (class_exists('\SilverStripe\Subsites\Model\Subsite')) {
            $subsiteId = \SilverStripe\Subsites\State\SubsiteState::singleton()->getSubsiteId();

            $list = $list->filter('SubsiteID', [
                -1,
                $subsiteId
            ]);
        }

        return $list;
    }
}
