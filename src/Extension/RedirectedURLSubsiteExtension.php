<?php

namespace SilverStripe\RedirectedURLs\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Subsites\Model\Subsite;

if (!class_exists('\SilverStripe\Subsites\Model\Subsite')) {
    return;
}

class RedirectedURLSubsiteExtension extends Extension
{
    private static $has_one = [
        'Subsite' => \SilverStripe\Subsites\Model\Subsite::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $subsites = Subsite::accessible_sites(['CMS_Access_RedirectedURLAdmin'], false)->map('ID', 'Title')->toArray();
        $fields->removeByName('SubsiteID');
        $fields->addFieldToTab(
            'Root.Main',
            DropdownField::create(
                'SubsiteID',
                _t(__CLASS__ . '.Subsite', 'Subsite'),
                [
                    -1 => _t(__CLASS__ . '.SubsiteAll', 'All subsites'),
                    0 => _t(__CLASS__ . '.SubsiteMain', 'Main site'),
                ] + $subsites,
                $this->owner->SubsiteID
            )
        );
    }


    public function filterBestRedirectedURLMatch()
    {
        $subsiteID = \SilverStripe\Subsites\State\SubsiteState::singleton()->getSubsiteId();

        if ($this->owner->SubsiteID === -1) {
            return true;
        } if ($this->owner->SubsiteID) {
            if ($subsiteID !== $this->owner->SubsiteID) {
                return false;
            }
        } else if ($subsiteID) {
            return false;
        }
    }
}
