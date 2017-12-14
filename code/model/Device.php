<?php
/**
 * Device.php
 *
 * @author Bram de Leeuw
 * Date: 14/06/2017
 */

namespace Broarm\EventTickets\App;

use Convert;
use DataObject;
use FieldList;
use LiteralField;
use ReadonlyField;
use Tab;
use TabSet;
use TextareaField;
use TextField;
use BaconQrCode;

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
 * @method \ManyManyList Members()
 */
class Device extends DataObject
{
    private static $db = array(
        'Note' => 'Text',
        'Token' => 'Varchar(255)',
        'UniqueID' => 'Varchar(255)',
        'Brand' => 'Varchar(255)',
        'Model' => 'Varchar(255)'
    );

    private static $has_one = array(
        'Members' => 'Member'
    );

    private static $has_many = array(
        //'History' => 'DeviceHistory'
    );

    private static $indexes = array(
        'Token' => 'unique("Token")'
    );

    private static $summary_fields = array(
        'Title' => 'Name',
        'Brand' => 'Brand',
        'Model' => 'Model',
        'Created.Nice' => 'Connected on'
    );

    public function getCMSFields()
    {
        $fields = new FieldList(new TabSet('Root', $mainTab = new Tab('Main')));
        $fields->addFieldsToTab('Root.Main', array(
            TextField::create('Title', 'Name'),
            TextareaField::create('Note', 'Note'),
            ReadonlyField::create('Token', 'Token'),
            ReadonlyField::create('UniqueID', 'UniqueID'),
            ReadonlyField::create('Brand', 'Brand'),
            ReadonlyField::create('Model', 'Model')
        ));

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
     * Returns the singular name without the namespaces
     *
     * @return string
     */
    public function singular_name()
    {
        $name = explode('\\', parent::singular_name());
        return trim(end($name));
    }

    /**
     * Find or make a new device
     *
     * @param $uniqueID
     * @param $brand
     * @param $model
     *
     * @return DataObject|null|static
     */
    public static function findOrMake($uniqueID, $brand = null, $model = null)
    {
        if (!$device = self::get()->find('UniqueID', $uniqueID)) {
            $device = self::create();
            $device->UniqueID = $uniqueID;
            $device->Brand = $brand;
            $device->Model = $model;
            $device->write();
        }

        return $device;
    }
}
