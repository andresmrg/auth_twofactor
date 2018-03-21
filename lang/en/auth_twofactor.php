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
 * Strings for component 'auth_twofactor', language 'en'.
 *
 * @package   auth_twofactor
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Two factor authentication';
$string['auth_twofactordescription'] = 'This method removes any way for users to create their own accounts.  All accounts must be manually created by the admin user.';
$string['expiration'] = 'Enable password expiry';
$string['expiration_desc'] = 'Allow passwords to expire after a specified time.';
$string['expiration_warning'] = 'Notification threshold';
$string['expiration_warning_desc'] = 'Number of days before password expiry that a notification is issued.';
$string['passwdexpiretime'] = 'Password duration';
$string['passwdexpiretime_desc'] = 'Length of time for which a password is valid.';
$string['passwdexpire_settings'] = 'Password expiry settings';
$string['enter_verification'] = 'A verification code was sent to your cellphone, please enter the code here to continue.';
$string['enter_phone'] = 'Your mobile phone number is required in your profile.';
$string['verificationcode'] = 'Enter Verification Code';
$string['incorrectcode'] = 'The verification code is not correct, you have {$a} attempts left.';
$string['noattemptsleft'] = 'You have reached the maximum number of attempts. Please try to login again in {$a}.';
$string['verification_page'] = 'Verification Page.';
$string['phone'] = 'Mobile Phone Number';
$string['phone_example'] = 'e.g. +313453039499';
$string['wronglogin'] = 'Something wrong happened. Please report to an administrator if you see this message.';
$string['phonenotupdated'] = 'Ups! Your mobile phone number could not be updated. Please try again or contact an administrator.';
$string['phoneupdatesuccess'] = 'Your mobile phone number was updated successfully';
$string['debug'] = 'Enable debug option';
$string['debug_desc'] = 'Don\'t enable on Production! This option is to test the verification code feature without sending the code to the user\'s mobile phone. You will be able to see the verification code in the page.';
$string['auth_twofactor_ip_range'] = 'IP Range';
$string['auth_twofactor_ip_range_desc'] = 'Put the range of IPs allowed to login. If the user is not within this range, a verification code will be sent to his/her mobile phone number through SMS.';
$string['auth_twofactor_timeout'] = 'Timeout in seconds';
$string['auth_twofactor_timeout_desc'] = 'Specify in seconds how long before the SMS code timeout.';
$string['auth_twofactor_attempts'] = 'Attempts';
$string['auth_twofactor_attempts_desc'] = 'How many attempts a user can try to login.';
$string['auth_twofactor_accesskey'] = 'Access Key';
$string['auth_twofactor_accesskey_desc'] = 'The access key from MessageBird is found in your account. You need to access directly to messagebird.com and generate one if you do not have one yet.';
$string['auth_twofactor_sender'] = 'Sender Name';
$string['auth_twofactor_sender_desc'] = 'Name of the originator of the message.';
$string['auth_twofactor_sender_desc'] = 'Name of the originator of the message.';
$string['eventmessage_sent'] = 'SMS Sent';