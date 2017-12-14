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
     *
     * @return string
     */
    public function index(SS_HTTPRequest $request)
    {
        $body = Convert::json2array($request->getBody());
        if (Authenticator::authenticate($request) && isset($body['ticket']) && $code = $body['ticket']) {
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
            return new SS_HTTPResponse(Convert::array2json(array(
                'Code' => CheckInValidator::MESSAGE_ERROR,
                'Message' => _t('TicketValidator.ERROR_TOKEN_AUTHENTICATION_FAILED', 'The request could not be authenticated, try to log in again.')
            )), 401);
        }
    }
}