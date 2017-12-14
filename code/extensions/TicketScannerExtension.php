<?php
/**
 * TicketScannerExtension.php
 *
 * @author Bram de Leeuw
 * Date: 14/06/2017
 */

namespace Broarm\EventTickets\App;

use DataExtension;
use FieldList;
use GridField;
use GridFieldConfig_RecordEditor;

/**
 * Class SiteConfigExtension
 *
 * @property TicketScannerExtension|\Member $owner
 * @property string                         TicketScannerAppToken
 * @method \HasManyList ScanDevices()
 */
class TicketScannerExtension extends DataExtension
{
    private static $has_many = array(
        'ScanDevices' => 'Broarm\EventTickets\App\Device'
    );

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->exists()) {
            $config = GridFieldConfig_RecordEditor::create();
            $fields->addFieldToTab(
                'Root.ScanDevices',
                GridField::create('ScanDevices', 'Scan devices', $this->owner->ScanDevices(), $config)
            );
        }

        return $fields;
    }
}
