<?php

namespace Broarm\EventTickets\App\Model;

use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * Class Device
 *
 * @property string Title
 * @property string Note
 * @property string Token
 * @property string UniqueID
 * @property string Brand
 * @property string Model
 * @property string DeviceID
 * @property string BundleID
 * @property string LastLogin
 * @method \ManyManyList Members()
 */
class Device extends DataObject
{
    private static $table_name = 'EventTickets_Device';
    
    private static $db = [
        'Note' => 'Text',
        'Token' => 'Varchar',
        'UniqueID' => 'Varchar',
        'LastLogin' => 'DBDatetime'
    ];

    private static $has_one = [
        'Members' => Member::class
    ];

    private static $indexes = [
        'Token' => [
            'type' => 'unique'
        ]
    ];

    private static $summary_fields = [
        'Title' => 'Name',
        'UniqueID' => 'Device ID',
        'Created.Nice' => 'Connected on',
        'LastLogin.Nice' => 'Last use'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title', 'Name'),
            TextareaField::create('Note', 'Note'),
            ReadonlyField::create('Token', 'Token'),
            ReadonlyField::create('UniqueID', 'UniqueID'),
            ReadonlyField::create('Brand', 'Brand'),
            ReadonlyField::create('Model', 'Model')
        ]);

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    /**
     * Get the title
     *
     * @return string
     */
    public function getTitle()
    {
        if (($brand = $this->Brand) && $model = $this->Model) {
            return "{$brand}, {$model}";
        } else {
            return parent::getTitle();
        }
    }

    /**
     * Find or make a new device
     *
     * @param $uniqueID
     * @param null $brand
     * @param null $model
     * @return Device|DataObject|null
     * @throws \ValidationException
     */
    public static function findOrMake($uniqueID, $brand = null, $model = null)
    {
        if (!$device = self::get()->find('UniqueID', $uniqueID)) {
            $device = self::create();
            $device->UniqueID = $uniqueID;
            $device->Brand = $brand;
            $device->Model = $model;
        }

        $device->LastLogin = time();
        $device->write();
        return $device;
    }
}
