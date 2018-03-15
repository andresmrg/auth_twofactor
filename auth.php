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

/**
 * Two Factor authentication plugin.
 *
 * @package    auth
 * @subpackage twofactor
 * @copyright  2018 onwards Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_twofactor extends auth_plugin_base {

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist. (Non-mnet accounts only!)
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password) {
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

    function user_authenticated_hook(&$user, $username, $password) {
        global $USER;

        // Get config values.
        $iprange        = get_config('auth_twofactor', 'iprange');
        $timeout        = get_config('auth_twofactor', 'timeout');
        $attempts       = get_config('auth_twofactor', 'attempts');

        // Store ip in vars to compare them.
        $iprangelist    = $this->get_ips($iprange);
        $lowip          = ip2long($iprangelist[0]);
        $highip         = ip2long($iprangelist[1]);
        $ip             = ip2long($this->get_real_ip_address());

        // Validate ip range.
        if (!($ip <= $highip && $lowip <= $ip)) {

            // Generate random number and send it to the user's phone.
            $randomcode = substr(str_shuffle(str_repeat('0123456789',5)),0,6);

            // Send the random code to the user's phone.
            $message = $this->send_code_to_user($randomcode);
            print_object($message);


            $encode = base64_encode($message->body);
            $u      = base64_encode(json_encode($user));
            $confirmurl = new moodle_url(
                '/auth/twofactor/confirm.php',
                array('ver' => $encode, 'mid' => $message->getId(), 'u' => $u)
            );

            redirect($confirmurl);

        }

    }

    function pre_loginpage_hook() {

        global $SESSION;

        print_object($SESSION);

        // if (isset($SESSION->mustattempt)) {
        //     $url = new moodle_url('/auth/twofactor/confirm.php', array('mid' => $SESSION['mid']));
        //     redirect($url);
        // }

        if ( isset($SESSION->timeout) && (time() - $SESSION->lastactivity < $SESSION->timeout)) {
            $url = new moodle_url('/auth/twofactor/confirm.php', array('timeout' => 'yes'));
            redirect($url);
        }

    }

    function loginpage_hook() {

        global $SESSION;

        print_object($SESSION);

        // if (isset($SESSION->mustattempt)) {
        //     $url = new moodle_url('/auth/twofactor/confirm.php', array('mid' => $SESSION['mid']));
        //     redirect($url);
        // }

        if ( isset($SESSION->timeout) && (time() - $SESSION->lastactivity < $SESSION->timeout)) {
            $url = new moodle_url('/auth/twofactor/confirm.php', array('timeout' => 'yes'));
            redirect($url);
        }

    }

    function send_code_to_user($randomcode) {

        require 'vendor/autoload.php';

        $accesskey              = get_config('auth_twofactor', 'accesskey');
        $sender                 = get_config('auth_twofactor', 'sender');

        $MessageBird            = new \MessageBird\Client($accesskey);
        $Message                = new \MessageBird\Objects\Message();
        $Message->originator    = $sender;
        $Message->recipients    = array('+573113636619');
        $Message->body          = $randomcode;
        $result                 = $MessageBird->messages->create($Message);

        return $result;

    }

    /**
     * Save the information into the DB to keep track.
     *
     * @param  int $code
     * @param  int $timeout
     * @param  int $attempts
     * @return boolean
     */
    function save_code($code, $timeout, $attempts) {

    }


}