<?php

namespace SilverStripe\RedirectedURLs\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\CMS\Model\RedirectorPage;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Specifies one URL redirection
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
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
        'FromBase' => 'Varchar(255)',
        'FromQuerystring' => 'Varchar(255)',
        'To' => 'Varchar(255)',
        'RedirectionType' => 'Enum("Internal,External", "Internal")',
    );

    /**
     * @var array
     * @config
     */
    private static $has_one = array(
        'LinkTo' => SiteTree::class,
    );

    /**
     * @var array
     * @config
     */
    private static $indexes = array(
        'From' => array(
            'type' => 'unique',
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
        'FromBase' => 'From URL base',
        'FromQuerystring' => 'From URL query parameters',
        'To' => 'To URL',
        'RedirectionType' => 'Redirection type',
    );

    /**
     * @var array
     * @config
     */
    private static $searchable_fields = array(
        'FromBase',
        'FromQuerystring',
        'To',
    );

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            // Include field which must be present when updateCMSFields is called on extensions
            $fromBaseField = $fields->fieldByName('Root.Main.FromBase');
            $fromBaseField->setDescription('e.g. /about-us.html');

            $fromQueryStringField = $fields->fieldByName('Root.Main.FromQuerystring');
            $fromQueryStringField->setDescription('e.g. page=1&num=5');

            $toField = $fields->fieldByName('Root.Main.To');
            $toField->setDescription('e.g. /about?something=5');

            $fields->replaceField('Root.Main.RedirectionType', OptionsetField::create(
                'RedirectionType',
                'Redirect to',
                [
                    'Internal' => _t(__CLASS__.'.FIELD_REDIRECTIONTYPE_OPTION_INTERNAL', 'A page on your website'),
                    'External' => _t(__CLASS__.'.FIELD_REDIRECTIONTYPE_OPTION_EXTERNAL', 'Another website'),
                ],
                'Internal'
            ));
            
            $fields->replaceField('LinkToID', $linkToWrapperField = Wrapper::create(TreeDropdownField::create(
                'LinkToID',
                'Page on your website',
                SiteTree::class
            )));

            $fields->fieldByName('Root.Main.To')->displayIf('RedirectionType')->isEqualTo('External');
            $linkToWrapperField->displayIf('RedirectionType')->isEqualTo('Internal');
        });

        return parent::getCMSFields();
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
                'name' => 'Create a redirect',
                'category' => 'Redirects'
            ),
            'REDIRECTEDURLS_EDIT' => array(
                'name' => 'Edit a redirect',
                'category' => 'Redirects',
            ),
            'REDIRECTEDURLS_DELETE' => array(
                'name' => 'Delete a redirect',
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
     * @param array $context
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

    /**
     * @return string
     */
    public function Link()
    {
        // Check external redirect
        if ($this->RedirectionType === 'External') {
            return $this->To ?: null;
        }

        // Check internal redirect
        /** @var SiteTree $linkTo */
        $linkTo = $this->LinkToID ? SiteTree::get()->byID($this->LinkToID) : null;
        if (empty($linkTo)) {
            return null;
        }

        // We shouldn't point to ourselves - that would create an infinite loop!  Return null since we have a
        // bad configuration
        if (intval($this->ID) === intval($linkTo->ID)) {
            return null;
        }

        // If we're linking to another redirectorpage then just return the URLSegment, to prevent a cycle of redirector
        // pages from causing an infinite loop.  Instead, they will cause a 30x redirection loop in the browser, but
        // this can be handled sufficiently gracefully by the browser.
        if ($linkTo instanceof RedirectorPage) {
            return $linkTo->regularLink();
        }

        // For all other pages, just return the link of the page.
        return $linkTo->RelativeLink();
    }
}
