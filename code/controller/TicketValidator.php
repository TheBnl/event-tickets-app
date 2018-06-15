<?php

namespace Broarm\EventTickets\App;

use Broarm\EventTickets\CheckInValidator;
use Controller;
use Convert;
use SS_HTTPRequest;
use SS_HTTPResponse;

/**
 * TicketValidator.php
 *
 * @author Bram de Leeuw
 * Date: 14/06/2017
 */
class TicketValidator extends Controller
{
    /**
     * Handle the request
     *
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse|string
     * @throws \SilverStripe\Omnipay\Exception\Exception
     */
    public function index(SS_HTTPRequest $request)
    {
        $body = Convert::json2array($request->getBody());
        if (Authenticator::authenticate($request) && isset($body['ticket']) && $code = $body['ticket']) {
            $validator = CheckInValidator::create();
            $result = $validator->validate($code);
            $attendee = $validator->getAttendee();
            $result['attendee'] = array(
                'name' => $attendee->getName(),
                'ticket' => $attendee->Ticket()->Title,
                'event' => $attendee->Event()->Title,
                'date' => date('d-m-Y H:i:s'),
                'type' => $result['Code'],
                'id' => uniqid()
            );

            switch ($result['Code']) {
                case CheckInValidator::MESSAGE_CHECK_OUT_SUCCESS:
                    $attendee->checkOut();
                    break;
                case CheckInValidator::MESSAGE_CHECK_IN_SUCCESS:
                    $attendee->checkIn();
                    break;
                default:
                    return Convert::array2json(array_change_key_case($result));
            }

            return Convert::array2json(array_change_key_case($result));
        } else {
            return new SS_HTTPResponse(Convert::array2json(array(
                'Code' => CheckInValidator::MESSAGE_TYPE_BAD,
                'Message' => _t('TicketValidator.ERROR_TOKEN_AUTHENTICATION_FAILED', 'The request could not be authenticated, try to log in again.')
            )), 401);
        }
    }
}
