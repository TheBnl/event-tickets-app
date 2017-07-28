<?php

namespace Broarm\EventTickets\App;

use Broarm\EventTickets\CheckInValidator;
use Controller;
use Convert;
use Director;
use SiteConfig;
use SS_HTTPRequest;

/**
 * TicketValidator.php
 *
 * @author Bram de Leeuw
 * Date: 14/06/2017
 */
class TicketValidator extends Controller
{
    const SCANNER_TOKEN = 'X-Ticket-Scanner-Token';
    const DEVICE_TOKEN = 'X-Ticket-Scanner-Device-Token';
    const UNIQUE_ID = 'X-Unique-Id';
    const BRAND = 'X-Brand';
    const MODEL = 'X-Model';
    const BUNDLE_ID = 'X-Bundle-Id';

    private static $app_bundle_id = null;

    private static $allowed_actions = array(
        'index'
    );

    private static $url_handlers = array(
        '$Code' => 'index'
    );

    /**
     * Handle the request
     *
     * @param SS_HTTPRequest $request
     *
     * @return string
     */
    public function index(SS_HTTPRequest $request)
    {
        if ($this->authenticate() && $code = $request->param('Code')) {
            $validator = CheckInValidator::create();
            $result = $validator->validate($code);
            switch ($result['Code']) {
                case CheckInValidator::MESSAGE_CHECK_OUT_SUCCESS:
                    $validator->getAttendee()->checkOut();
                    break;
                case CheckInValidator::MESSAGE_CHECK_IN_SUCCESS:
                    $validator->getAttendee()->checkIn();
                    break;
                default:
                    return Convert::array2json($result);
            }

            return Convert::array2json($result);
        } else {
            return $this->httpError(404, Convert::array2json(array(
                'Message' => _t('TicketValidator.WRONG_CONFIGURATION', 'Check your configuration settings'),
                'Type' => CheckInValidator::MESSAGE_TYPE_WARNING
            )));
        }
    }

    /**
     * Authenticate by the set headers
     *
     * @return bool
     */
    private function authenticate() {
        $scannerToken = $this->request->getHeader(self::SCANNER_TOKEN);
        $deviceToken = $this->request->getHeader(self::DEVICE_TOKEN);

        if (empty($scannerToken) || empty($deviceToken)) {
            return false;
        } elseif (!SiteConfig::current_site_config()->validateTicketScannerAppToken($scannerToken)) {
            return false;
        } elseif (!$device = Device::get()->find('DeviceToken', $deviceToken)) {
            return false;
        } else {
            /** @var Device $device */
            $device->UniqueID = $this->request->getHeader(self::UNIQUE_ID);
            $device->Brand = $this->request->getHeader(self::BRAND);
            $device->Model = $this->request->getHeader(self::MODEL);
            $device->BundleID = $this->request->getHeader(self::BUNDLE_ID);
            $device->write();
            return true;
        }
    }

    /**
     * Get the link to the validation controller
     *
     * @return string
     */
    public static function getLink()
    {
        return Director::absoluteURL('/validateticket/');
    }
}