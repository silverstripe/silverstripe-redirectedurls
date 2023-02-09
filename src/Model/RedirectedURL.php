<?php

namespace SilverStripe\RedirectedURLs\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Forms\TextField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\CMS\Model\RedirectorPage;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;

/**
 * Specifies one URL redirection
 *
 * @property string $FromBase
 * @property string $FromQuerystring
 * @property string $To
 * @property string $RedirectionType
 * @property int $RedirectCode
 * @property int $LinkToID
 * @property int $LinkToAssetID
 * @method SiteTree LinkTo()
 * @method File LinkToAsset()
 */
class RedirectedURL extends DataObject implements PermissionProvider
{

    private const REDIRECTION_TYPE_ASSET = 'Asset';
    private const REDIRECTION_TYPE_EXTERNAL = 'External';
    private const REDIRECTION_TYPE_INTERNAL = 'Internal';

    private static string $singular_name = 'Redirected URL';

    private static string $table_name = 'RedirectedURL';

    private static array $db = [
        'FromBase' => 'Varchar(255)',
        'FromQuerystring' => 'Varchar(255)',
        'To' => 'Varchar(255)',
        'RedirectionType' => 'Enum("Internal,External,Asset", "Internal")',
        'RedirectCode' => 'Int',
    ];

    private static array $has_one = [
        'LinkTo' => SiteTree::class,
        'LinkToAsset' => File::class,
    ];

    private static $indexes = [
        'From' => [
            'type' => 'unique',
            'columns' => [
                'FromBase',
                'FromQuerystring',
            ],
        ],
    ];

    private static array $summary_fields = [
        'FromBase' => 'From URL base',
        'FromQuerystring' => 'From URL query parameters',
        'To' => 'To URL',
        'LinkTo.Title' => 'Link To',
        'LinkToAsset.Title' => 'Link To File',
        'RedirectionType' => 'Redirection type',
        'RedirectCode' => 'Redirect code',
    ];

    private static array $searchable_fields = array(
        'FromBase',
        'FromQuerystring',
        'To',
    );

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            $fields->removeByName([
                'FromBase',
                'FromQuerystring',
                'RedirectCode',
                'To',
                'RedirectionType',
                'LinkToID',
                'LinkToAsset',
            ]);

            $fields->addFieldsToTab(
                'Root.Main',
                [
                    $fromBaseField = TextField::create(
                        'FromBase',
                        _t(static::class.'.FIELD_TITLE_FROMBASE', 'From base')
                    ),
                    $fromQueryStringField = TextField::create(
                        'FromQuerystring',
                        _t(static::class.'.FIELD_TITLE_FROMQUERYSTRING', 'From querystring')
                    ),
                    DropdownField::create(
                        'RedirectCode',
                        _t(static::class.'.FIELD_TITLE_REDIRECTCODE', 'Redirect code'),
                        $this->getCodes()
                    ),
                    OptionsetField::create(
                        'RedirectionType',
                        _t(static::class.'.FIELD_TITLE_REDIRECTIONTYPE', 'Redirect to'),
                        [
                            self::REDIRECTION_TYPE_INTERNAL =>
                                _t(static::class.'.FIELD_REDIRECTIONTYPE_OPTION_INTERNAL', 'A page on your website'),
                            self::REDIRECTION_TYPE_EXTERNAL =>
                                _t(static::class.'.FIELD_REDIRECTIONTYPE_OPTION_EXTERNAL', 'Another website'),
                            self::REDIRECTION_TYPE_ASSET =>
                                _t(static::class.'.FIELD_REDIRECTIONTYPE_OPTION_ASSET', 'An asset on your website'),
                        ],
                        self::REDIRECTION_TYPE_INTERNAL
                    ),
                    $toField = TextField::create(
                        'To',
                        _t(static::class.'.FIELD_TITLE_TO', 'To')
                    ),
                    $linkToWrapperField = Wrapper::create(TreeDropdownField::create(
                        'LinkToID',
                        _t(static::class.'.FIELD_TITLE_LINKTOID', 'Page on your website'),
                        SiteTree::class
                    )),
                    $linkToAssetWrapperField = Wrapper::create(UploadField::create(
                        'LinkToAsset',
                        _t(static::class.'.FIELD_TITLE_LINKTOASSETID', 'Asset on your website')
                    )),
                ]
            );

            $fromBaseField->setDescription(_t(static::class.'.FIELD_DESCRIPTION_FROMBASE', 'e.g. /about-us.html'));

            $fromQueryStringField->setDescription(
                _t(static::class.'.FIELD_DESCRIPTION_FROMQUERYSTRING', 'e.g. page=1&num=5')
            );

            $toField->setDescription(_t(static::class.'.FIELD_DESCRIPTION_TO', 'e.g. /about?something=5'));
            $toField->displayIf('RedirectionType')->isEqualTo(self::REDIRECTION_TYPE_EXTERNAL);

            $linkToWrapperField->displayIf('RedirectionType')->isEqualTo(self::REDIRECTION_TYPE_INTERNAL);

            $linkToAssetWrapperField->displayIf('RedirectionType')->isEqualTo(self::REDIRECTION_TYPE_ASSET);
        });

        return parent::getCMSFields();
    }

    /**
     * @return void
     */
    public function populateDefaults()
    {
        $this->RedirectCode = $this->getRedirectCodeDefault();
    }

    /**
     * @return int
     */
    private function getRedirectCodeDefault()
    {
        $redirectCodeValue = 301;

        $defaultRedirectCode = intval(Config::inst()->get(RedirectedURL::class, 'default_redirect_code'));
        if ($defaultRedirectCode > 0) {
            $redirectCodeValue = $defaultRedirectCode;
        }

        return $redirectCodeValue;
    }

    /**
     * @return array
     */
    protected function getCodes()
    {
        return [
            301 => _t(static::class.'.CODE_301', '301 - Permanent'),
            302 => _t(static::class.'.CODE_302', '302 - Temporary'),
        ];
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
        $val = rtrim((string) $val, '?');
        $this->setField('FromQuerystring', strtolower((string) $val));
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
        switch ($this->RedirectionType) {
            case self::REDIRECTION_TYPE_INTERNAL:
                return $this->getLinkToLink();
            case self::REDIRECTION_TYPE_ASSET:
                return $this->getLinkToAssetLink();
            default:
                return $this->To;
        }
    }

    private function getLinkToLink(): ?string
    {
        // Check internal redirect
        $linkTo = $this->LinkTo();

        if (!$linkTo || !$linkTo->exists()) {
            return null;
        }

        // We shouldn't point to ourselves - that would create an infinite loop!  Return null since we have a
        // bad configuration
        if ($this->ID === $linkTo->ID) {
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

    private function getLinkToAssetLink(): ?string
    {
        $linkToAsset = $this->LinkToAsset();

        // Note: exists() includes checking that the file behind this Asset exists in the filesystem
        if (!$linkToAsset || !$linkToAsset->exists()) {
            return null;
        }

        // We shouldn't point to ourselves - that would create an infinite loop!  Return null since we have a
        // bad configuration
        if ($this->ID === $linkToAsset->ID) {
            return null;
        }

        return $linkToAsset->Link();
    }
}
