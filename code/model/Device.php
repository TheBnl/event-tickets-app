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
 * @property string DeviceToken
 * @property string UniqueID
 * @property string Brand
 * @property string Model
 * @property string DeviceID
 * @property string BundleID
 * @method \SiteConfig|TicketScannerExtension Parent()
 */
class Device extends DataObject
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'Note' => 'Text',
        'DeviceToken' => 'Varchar(255)',
        'UniqueID' => 'Varchar(255)',
        'Brand' => 'Varchar(255)',
        'Model' => 'Varchar(255)',
        'BundleID' => 'Varchar(255)',
    );

    private static $has_one = array(
        'Parent' => 'SiteConfig'
    );

    private static $indexes = array(
        'DeviceToken' => 'unique("DeviceToken")'
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
            ReadonlyField::create('UniqueID', 'UniqueID'),
            ReadonlyField::create('Brand', 'Brand'),
            ReadonlyField::create('Model', 'Model')
        ));

        if ($this->exists()) {
            $fields->addFieldsToTab('Root.Main', array(
                LiteralField::create('QRCode', "
                    <div class='field readonly'>
                        <label class='left'>Device Setup QR</label>
                        <div class='middleColumn'>
                            <img src='{$this->generateQRCode()}' 
                                 style='border: 1px solid #b3b3b3;border-radius:4px;' 
                                 width='256' 
                                 height='256'/>
                        </div>
                    </div>"
                )
            ));
        }

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    /**
     * Update the title and token before write
     */
    public function onBeforeWrite()
    {
        if (empty($this->Title)) {
            $this->Title = "{$this->Brand}, {$this->Model}";
        }

        if (empty($this->DeviceToken)) {
            $this->DeviceToken = $this->generateDeviceToken();
        }

        parent::onBeforeWrite();
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
     * Create a base64 qr code from the app setup @see getAppSetup()
     *
     * @return string
     */
    public function generateQRCode()
    {
        $renderer = new BaconQrCode\Renderer\Image\Png();
        $renderer->setHeight(256);
        $renderer->setWidth(256);
        $writer = new BaconQrCode\Writer($renderer);
        return "data://image/png;base64," . base64_encode($writer->writeString($this->getAppSetup()));
    }

    /**
     * Compile the app setup, this contains:
     * the api path for the app to call
     * the site name for in app display
     * the site and device token for authentication
     *
     * @return string
     */
    public function getAppSetup()
    {
        return Convert::array2json(array(
            'api' => TicketValidator::getLink(),
            'site' => $this->Parent()->Title,
            'token' => $this->Parent()->TicketScannerAppToken,
            'deviceToken' => $this->DeviceToken
        ));
    }

    /**
     * Generate a unique device ID
     *
     * @return string
     */
    public function generateDeviceToken() {
        return uniqid($this->ID);
    }

    public function canView($member = null)
    {
        return $this->Parent()->canView($member);
    }

    public function canEdit($member = null)
    {
        return $this->Parent()->canEdit($member);
    }

    public function canDelete($member = null)
    {
        return $this->Parent()->canDelete($member);
    }

    public function canCreate($member = null)
    {
        return $this->Parent()->canCreate($member);
    }
}
