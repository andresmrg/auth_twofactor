<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Authentication Plugin: Two Authentication
 * Just does a simple check against the moodle database.
 *
 * @package    auth_twofactor
 * @copyright  2018 onwards Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');
require_once("$CFG->dirroot/user/profile/lib.php");

/**
 * Two Factor authentication plugin.
 *
 * @package    auth_twofactor
 * @copyright  2018 onwards Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_twofactor extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'twofactor';
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist. (Non-mnet accounts only!)
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password) {
        global $CFG, $DB, $USER;
        if (!$user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            return false;
        }
        if (!validate_internal_user_password($user, $password)) {
            return false;
        }
        if ($password === 'changeme') {
            // force the change - this is deprecated and it makes sense only for manual auth,
            // because most other plugins can not change password easily or
            // passwords are always specified by users
            set_user_preference('auth_forcepasswordchange', true, $user->id);
        }
        return true;
    }

    /**
     * Returns an array with the IPs. They usually comes as a single string.
     *
     * @param  string $iprange
     * @return array
     */
    function get_ips($iprange) {
        return explode('-', $iprange);
    }

    /**
     * Obtain the user's ip.
     * @return int
     */
    function get_real_ip_address() {

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) { // Check ip from share internet.
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { // To check ip is pass from proxy.
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;

    }

    /**
     * Post authentication hook.
     * This method is called from authenticate_user_login() for all enabled auth plugins.
     *
     * @param object $user user object, later used for $USER
     * @param string $username (with system magic quotes)
     * @param string $password plain text password (with system magic quotes)
     */
    function user_authenticated_hook(&$user, $username, $password) {

        global $SESSION;

        if (is_siteadmin($user)) {
            return;
        }

        if (isset($SESSION->justloggedin) && $SESSION->justloggedin) {
            return;
        }

        // Get config values.
        $iprange        = get_config('auth_twofactor', 'iprange');
        $timeout        = get_config('auth_twofactor', 'timeout');
        $attempts       = get_config('auth_twofactor', 'attempts');
        $debug          = get_config('auth_twofactor', 'debug');

        // Store ip in vars to compare them.
        $iprangelist    = $this->get_ips($iprange);
        $lowip          = ip2long($iprangelist[0]);
        $highip         = ip2long($iprangelist[1]);
        $ip             = ip2long($this->get_real_ip_address());

        $u              = base64_encode(json_encode($user));

        // Validate if the user has any phone number, otherwise the user must add it.
        $emptyphone = (empty($user->phone2) && empty($user->phone1) && !is_siteadmin());
        if ($emptyphone) {
            $urltogo = new moodle_url('/auth/twofactor/profile.php', array('u' => $u));
            redirect($urltogo);
        }

        // Validate ip range.
        if (!($ip <= $highip && $lowip <= $ip) && !is_siteadmin()) {

            // Generate random number and send it to the user's phone.
            $randomcode = substr(str_shuffle(str_repeat('0123456789',5)),0,6);

            // Send the user data, so we can authenticate it from the confirm page.
            $u          = base64_encode(json_encode($user));

            // Send the random code to the user's phone.
            if (!$debug) {
                $message    = $this->send_code_to_user($randomcode, $user);
                $urlparams  = array(
                    'mid' => $message->getId(),
                    'u'   => $u
                );
            } else {
                $encode    = base64_encode($randomcode);
                $urlparams  = array(
                    'ver' => $encode, // THIS SHOULD BE DELETED.
                    'u'   => $u
                );
            }

            redirect( new moodle_url('/auth/twofactor/confirm.php', $urlparams) );

        }

    }

    /**
     * Hook for overriding behaviour of login page.
     * This method is called from login/index.php page for all enabled auth plugins.
     *
     * @global object
     * @global object
     */
    function loginpage_hook() {

        global $SESSION;

        // If the user needs to be verified, and he attempts to go back, redirect him to
        // the verification page to make the attempts. It won't redirect if all attempts
        // are consumed.
        if (isset($SESSION->mustattempt)) {
            $params = array('mid' => $SESSION->mid, 'ver' => $SESSION->ver);
            $url = new moodle_url('/auth/twofactor/confirm.php', $params);
            redirect($url);
        }

        // Redirect the user if the timeout hasn't expired yet.
        if ( isset($SESSION->timeout) && (time() - $SESSION->lastactivity < $SESSION->timeout)) {
            $url = new moodle_url('/auth/twofactor/confirm.php', array('timeout' => 'yes'));
            redirect($url);
        }

    }

    /**
     * Sends the verification code to the user.
     *
     * @param  int    $randomcode
     * @return mixed               Returns object if the message was deliver, false otherwise.
     */
    function send_code_to_user($randomcode, $user) {

        require 'vendor/autoload.php';

        // Try one of the phone numbers from their profile.
        $phonenumber            = (!empty($user->phone2)) ? $user->phone2 : $user->phone1;
        $accesskey              = get_config('auth_twofactor', 'accesskey');
        $sender                 = get_config('auth_twofactor', 'sender');

        $MessageBird            = new \MessageBird\Client($accesskey);
        $Message                = new \MessageBird\Objects\Message();
        $Message->originator    = $sender;
        $Message->recipients    = array($phonenumber);
        $Message->body          = $randomcode;
        $result                 = $MessageBird->messages->create($Message);

        if (!empty($result)) {
            $event = \auth_twofactor\event\message_sent::create(array(
                'objectid'      => $randomcode,
                'relateduserid' => $user->id,
                'userid'        => $user->id,
                'context'       => context_system::instance()
            ));
            $event->trigger();
        }

        return $result;

    }

}
