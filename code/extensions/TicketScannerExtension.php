<?php
/**
 * TicketScannerExtension.php
 *
 * @author Bram de Leeuw
 * Date: 14/06/2017
 */

namespace Broarm\EventTickets;

use DataExtension;
use FieldList;
use GridField;
use GridFieldConfig_RecordEditor;
use PasswordEncryptor;
use RandomGenerator;

/**
 * Class SiteConfigExtension
 * 
 * @property TicketScannerExtension|\SiteConfig $owner
 * @property string                             TicketScannerAppToken
 * @method \HasManyList ScanDevices()
 */
class TicketScannerExtension extends DataExtension
{
    private static $db = array(
        'TicketScannerAppToken' => 'Varchar(255)'
    );

    private static $has_one = array();

    private static $has_many = array(
        'ScanDevices' => 'Broarm\EventTickets\Device'
    );

    private static $many_many = array();
    private static $defaults = array();
    private static $belongs_many_many = array();
    private static $searchable_fields = array();
    private static $summary_fields = array();
    private static $translate = array();

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->exists()) {
            $config = GridFieldConfig_RecordEditor::create();
            $fields->addFieldToTab(
                'Root.Ticket Scanners',
                GridField::create('ScanDevices', 'Scan devices', $this->owner->ScanDevices(), $config)
            );
        }

        return $fields;
    }

    /**
     * If no token yet exists, generate one.
     * todo: save this more secure
     */
    public function onBeforeWrite()
    {
        if (empty($this->owner->TicketScannerAppToken)) {
            $this->owner->TicketScannerAppToken = $this->owner->generateToken();
        }

        parent::onBeforeWrite();
    }

    /**
     * Check if the token is correct
     *
     * @param $token
     *
     * @return bool
     */
    public function validateTicketScannerAppToken($token)
    {
        return (bool)($this->owner->TicketScannerAppToken === $token);
    }

    /**
     * Generate a unique token
     *
     * @return string
     */
    public function generateToken() {
        $generator = new RandomGenerator();
        $tokenString = $generator->randomToken();
        return substr($tokenString, 7);
    }
}
