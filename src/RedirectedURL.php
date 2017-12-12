<?php

namespace SilverStripe\RedirectedURLs;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Specifies one URL redirection
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 */
class RedirectedURL extends DataObject implements PermissionProvider
{

    /**
     * @var string
     */
    private static $singular_name = 'Redirected URL';

    /**
     * @var string
     */
    private static $table_name = 'RedirectedURL';

    /**
     * @var array
     */
    private static $db = [
        'FromBase' => 'Varchar(255)',
        'FromQuerystring' => 'Varchar(255)',
        'To' => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'From' => [
            'type' => 'unique',
            'columns' => [
                'FromBase',
                'FromQuerystring'
            ],
        ],
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'FromBase' => 'From URL base',
        'FromQuerystring' => 'From URL query parameters',
        'To' => 'To URL',
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'FromBase',
        'FromQuerystring',
        'To',
    ];

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fromBaseField = $fields->fieldByName('Root.Main.FromBase');
        $fromBaseField->setDescription('e.g. /about-us.html');

        $fromQueryStringField = $fields->fieldByName('Root.Main.FromQuerystring');
        $fromQueryStringField->setDescription('e.g. page=1&num=5');

        $toField = $fields->fieldByName('Root.Main.To');
        $toField->setDescription('e.g. /about?something=5');

        return $fields;
    }

    /**
     * @param $val
     */
    public function setFrom($val)
    {
        if (strpos($val, '?') !== false) {
            list($base, $querystring) = explode('?', $val, 2);
        } else {
            $base = $val;
            $querystring = null;
        }
        $this->setFromBase($base);
        $this->setFromQuerystring($querystring);
    }

    /**
     * @return mixed|string
     */
    public function getFrom()
    {
        $url = $this->FromBase;
        if ($this->FromQuerystring) {
            $url .= "?" . $this->FromQuerystring;
        }
        return $url;
    }

    /**
     * @param $val
     */
    public function setFromBase($val)
    {
        if ($val[0] != '/') {
            $val = "/$val";
        }
        if ($val != '/') {
            $val = rtrim($val, '/');
        }
        $val = rtrim($val, '?');
        $this->setField('FromBase', strtolower($val));
    }

    /**
     * @param $val
     */
    public function setFromQuerystring($val)
    {
        $val = rtrim($val, '?');
        $this->setField('FromQuerystring', strtolower($val));
    }

    /**
     * Helper for bulkloader {@link: RedirectedURLAdmin.getModelImporters}
     *
     * @param string $from The From URL to search
     * @return DataObject {@link: RedirectedURL}
     */
    public function findByFrom($from)
    {
        if ($from[0] != '/') {
            $from = "/$from";
        }
        $from = rtrim($from, '?');

        if (strpos($from, '?') !== false) {
            list($base, $querystring) = explode('?', strtolower($from), 2);
        } else {
            $base = $from;
            $querystring = null;
        }

        $SQL_base = Convert::raw2sql($base);
        $SQL_querystring = Convert::raw2sql($querystring);

        $qsClause = "AND \"FromQuerystring\" IS NULL";
        if ($querystring) {
            $qsClause = "AND \"FromQuerystring\" = '$SQL_querystring'";
        }

        return RedirectedURL::get()->where("\"FromBase\" = '$SQL_base' $qsClause")->limit(1)->first();
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'REDIRECTEDURLS_CREATE' => [
                'name' => 'Create a redirect',
                'category' => 'Redirects'
            ],
            'REDIRECTEDURLS_EDIT' => [
                'name' => 'Edit a redirect',
                'category' => 'Redirects',
            ],
            'REDIRECTEDURLS_DELETE' => [
                'name' => 'Delete a redirect',
                'category' => 'Redirects',
            ],
        ];
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canView($member = null)
    {
        return true;
    }

    /**
     * @param null $member
     * @param array $context
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('REDIRECTEDURLS_CREATE');
    }

    /**
     * @param null $member
     * @return bool|int
     */
    public function canEdit($member = null)
    {
        return Permission::check('REDIRECTEDURLS_EDIT');
    }

    /**
     * @param null $member
     * @return bool|int
     */
    public function canDelete($member = null)
    {
        return Permission::check('REDIRECTEDURLS_DELETE');
    }
}
