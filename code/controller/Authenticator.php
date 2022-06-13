<?php

namespace Broarm\EventTickets\App;

use Broarm\EventTickets\CheckInValidator;
use Controller;
use Convert;
use DataObject;
use Director;
use Firebase\JWT\JWT;
use Permission;
use SilverStripe\Omnipay\Exception\Exception;
use SiteConfig;
use SS_HTTPRequest;
use SS_HTTPResponse;
use UnexpectedValueException;

/**
 * TicketValidator.php
 *
 * @author Bram de Leeuw
 * Date: 14/06/2017
 */
class Authenticator extends Controller
{
    const TYPE_ACCOUNT = 'ACCOUNT';
    const VALIDATE_TICKET = 'eventtickets/validate';
    const VALIDATE_TOKEN = 'eventtickets/authenticator/validatetoken';

    private static $icon = 'favicon-152.png';

    private static $validate_path = '';

    private static $token_header = 'X-Authorization';

    private static $jwt_alg = 'HS256';//'HS512';

    private static $jwt_nbf_offset = 0;

    private static $jwt_exp_offset = 9000;

    private static $allowed_actions = array(
        'authenticator',
        'validateToken'
    );

    /**
     * Handle the request
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     * @throws Exception
     * @throws \ValidationException
     */
    public function index(SS_HTTPRequest $request)
    {
        $body = Convert::json2array($request->getBody());

        if (
            isset($body['username']) &&
            isset($body['password']) &&
            isset($body['uniqueId'])
        ) {
            /** @var \Authenticator $authClass */
            $authClass = \Authenticator::get_default_authenticator();
            $member = $authClass::authenticate(array(
                'Email' => $body['username'],
                'Password' => $body['password'],
            ));

            if ($member->exists()) {
                if (!Permission::check('HANDLE_CHECK_IN', 'any', $member)) {
                    return new SS_HTTPResponse(Convert::array2json(array(
                        'message' => _t('TicketValidator.ERROR_USER_PERMISSIONS', 'You don’t have enough permissions to handle the check in.')
                    )), 401);
                }
                // find or create device and save token in it
                $device = Device::findOrMake($body['uniqueId']);

                // create the token
                $tokenData = array(
                    'iat' => $issuedAt = time(),
                    'jti' => $device->ID,
                    'iss' => Director::absoluteBaseURL(),
                    'nbf' => $notBefore = $issuedAt + self::config()->get('jwt_nbf_offset'),
                    'exp' => $notBefore + self::config()->get('jwt_exp_offset'),
                    'data' => [
                        'memberId' => $member->ID,
                        'deviceId' => $device->ID
                    ]
                );

                $token = JWT::encode($tokenData, self::jwtSecretKey(), self::config()->get('jwt_alg'));
                $device->Token = $member->encryptWithUserSettings($token);
                $member->ScanDevices()->add($device);

                $siteConfig = SiteConfig::current_site_config();
                return new SS_HTTPResponse(Convert::array2json(array(
                    'id' => $device->ID,
                    'type' => self::TYPE_ACCOUNT,
                    'title' => $siteConfig->Title,
                    'image' => Director::absoluteBaseURL() . self::config()->get('icon'),
                    'token' => $token,
                    'validatePath' => Controller::join_links(Director::absoluteBaseURL(), self::VALIDATE_TICKET),
                    'validateTokenPath' => Controller::join_links(Director::absoluteBaseURL(), self::VALIDATE_TOKEN)
                )), 200);
            }
        }

        return new SS_HTTPResponse(Convert::array2json(array(
            'message' => _t('TicketValidator.ERROR_WRONG_AUTHENTICATION', 'Wrong username or password given.')
        )), 401);
    }

    /**
     * Authenticate by given JWT
     *
     * @param SS_HTTPRequest $request
     * @return bool|SS_HTTPResponse
     * @throws Exception
     */
    public static function authenticate(SS_HTTPRequest $request)
    {
        if ($header = $request->getHeader(self::config()->get('token_header'))) {
            list($jwt) = sscanf($header, 'Bearer %s');
            if (!empty($jwt)) {
                try {
                    $decoded = JWT::decode($jwt, self::jwtSecretKey(), array(self::config()->get('jwt_alg')));
                } catch (UnexpectedValueException $e) {
                    return new SS_HTTPResponse(Convert::array2json(array(
                        'code' => CheckInValidator::MESSAGE_TYPE_BAD,
                        'message' => $e->getMessage()
                    )), 401);
                }

                /** @var Device $device */
                /** @var \Member $member */
                if (
                    ($device = DataObject::get_by_id(Device::class, $decoded->data->deviceId)) &&
                    ($member = DataObject::get_by_id('Member', $decoded->data->memberId))
                ) {
                    return $device->Token === $member->encryptWithUserSettings($jwt);
                }
            }
        };

        return false;
    }

    /**
     * @todo refresh token when valid
     *
     * @param SS_HTTPRequest $request
     * @return bool|SS_HTTPResponse
     * @throws Exception
     */
    public function validateToken(SS_HTTPRequest $request) {
        return self::authenticate($request);
    }

    /**
     * Get the token or return an error response
     * @return mixed
     * @throws Exception
     */
    private static function jwtSecretKey()
    {
        if (!defined('JWT_SECRET_KEY')) {
            throw new Exception(_t('TicketValidator.ERROR_SERVER_SETUP', 'The server is not set up properly, contact your site administrator.'));
        }

        return JWT_SECRET_KEY;
    }
}
