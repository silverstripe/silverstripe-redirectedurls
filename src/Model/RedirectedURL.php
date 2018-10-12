<?php

namespace SilverStripe\RedirectedURLs\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Specifies one URL redirection
 *
 * @package redirectedurls
 * @author  sam@silverstripe.com
 * @property string $FromBase
 * @property string $FromQuerystring
 * @property string $To
 */
class RedirectedURL extends DataObject implements PermissionProvider
{

    /**
     * @var string
     * @config
     */
    private static $singular_name = 'Redirected URL';

    /**
     * @var string
     * @config
     */
    private static $table_name = 'RedirectedURL';

    /**
     * @var array
     * @config
     */
    private static $db = array(
        'FromBase'        => 'Varchar(255)',
        'FromQuerystring' => 'Varchar(255)',
        'To'              => 'Varchar(255)',
    );

    /**
     * List of one-to-one relationships. {@link DataObject::$has_one}
     *
     * @var array
     * @config
     */
    private static $has_one = [
        'Page' => SiteTree::class
    ];

    /**
     * @var array
     * @config
     */
    private static $indexes = array(
        'From' => array(
            'type'    => 'unique',
            'columns' => array(
                'FromBase',
                'FromQuerystring',
            ),
        ),
    );

    /**
     * @var array
     * @config
     */
    private static $summary_fields = array(
        'FromBase'        => 'From URL base',
        'FromQuerystring' => 'From URL query parameters',
        'To'              => 'To URL',
        'Page.MenuTitle'  => 'To Page'
    );

    /**
     * @var array
     * @config
     */
    private static $searchable_fields = array(
        'FromBase',
        'FromQuerystring',
        'To',
        'PageID'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('PageID');

        $fromBaseField = $fields->fieldByName('Root.Main.FromBase');
        $fromBaseField->setDescription('e.g. /about-us.html');

        $fromQueryStringField = $fields->fieldByName('Root.Main.FromQuerystring');
        $fromQueryStringField->setDescription('e.g. page=1&num=5');

        $toField = $fields->fieldByName('Root.Main.To');
        $fields->removeByName('To');
        $toField->setTitle($toField->Title() . ' (URL)');

        $pageField = TreeDropdownField::create('PageID', $this->fieldLabel('Page'), SiteTree::class);
        $pageField->setTitle($pageField->Title() . ' ' . _t(__CLASS__ . '.ExistingPage', '(Existing page)'));

        $toSeperator = LiteralField::create('togroupseperator', '<strong style="margin: 0 10px 0 1px;">' . _t(__CLASS__ . '.Or', 'OR') . '</strong>');

        $toGroup = FieldGroup::create($this->fieldLabel('To'), [
            $toField,
            $toSeperator,
            $pageField
        ])->setDescription('e.g. /about?something=5 or choose an existing page in the dropdown');

        $fields->addFieldToTab('Root.Main', $toGroup);

        return $fields;
    }

    /**
     * @param string $val
     * @return $this
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

        return $this;
    }

    /**
     * @return string
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
     * @param string $val
     * @return $this
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

        return $this;
    }

    /**
     * @param string $val
     * @return $this
     */
    public function setFromQuerystring($val)
    {
        $val = rtrim($val, '?');
        $this->setField('FromQuerystring', strtolower($val));

        return $this;
    }

    /**
     * Helper for bulkloader {@link: RedirectedURLAdmin.getModelImporters}
     *
     * @param string $from The From URL to search
     * @return self {@link: RedirectedURL}
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

        $filter = array(
            'FromBase' => $base,
        );

        if ($querystring) {
            $filter['FromQuerystring'] = $querystring;
        }

        return RedirectedURL::get()->filter($filter)->first();
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return array(
            'REDIRECTEDURLS_CREATE' => array(
                'name'     => 'Create a redirect',
                'category' => 'Redirects'
            ),
            'REDIRECTEDURLS_EDIT'   => array(
                'name'     => 'Edit a redirect',
                'category' => 'Redirects',
            ),
            'REDIRECTEDURLS_DELETE' => array(
                'name'     => 'Delete a redirect',
                'category' => 'Redirects',
            )
        );
    }

    /**
     * @param Member|null $member
     * @return bool
     */
    public function canView($member = null)
    {
        return true;
    }

    /**
     * @param Member|null $member
     * @param array       $context
     * @return bool
     */
    public function canCreate($member = null, $context = array())
    {
        return Permission::checkMember($member, 'REDIRECTEDURLS_CREATE');
    }

    /**
     * @param Member|null $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        return Permission::checkMember($member, 'REDIRECTEDURLS_EDIT');
    }

    /**
     * @param Member|null $member
     * @return bool
     */
    public function canDelete($member = null)
    {
        return Permission::checkMember($member, 'REDIRECTEDURLS_DELETE');
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->PageID) {
            $this->To = '';
        }
    }
}
