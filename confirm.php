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

require_once('../../config.php');
require_once('confirm_form.php');
require 'vendor/autoload.php';

@error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
@ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!

// Get url params.
$accesskey = get_config('auth_twofactor', 'accesskey');
$code      = optional_param('ver', 0, PARAM_NOTAGS);
$messageid = optional_param('mid', 0, PARAM_NOTAGS);
$istimeout = optional_param('timeout', 0, PARAM_NOTAGS);
$u         = optional_param('u', "", PARAM_NOTAGS);
$needphone = optional_param('phone', 0, PARAM_INT);

$debugcode = ( !empty($code) ) ? base64_decode($code) : "";

global $DB, $OUTPUT, $PAGE, $USER, $CFG, $SESSION;

// Set page layout and headings.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('popup');
$PAGE->set_url(new moodle_url('/auth/twofactor/confirm.php'));
$PAGE->set_title(get_string('verificationcode', 'auth_twofactor'));
$PAGE->set_heading(get_string('verificationcode', 'auth_twofactor'));

if (!empty($istimeout)) {

    // If the timeout expired, destroy the sessions and redirect to the login.
    // They should be able to try again.
    $remainingtime = $SESSION->timeout - (time() - $SESSION->lastactivity);
    if (time() - $SESSION->lastactivity >= $SESSION->timeout) {

        // Unset sessions.
        $SESSION->lastactivity = null;
        $SESSION->timeout = null;
        redirect($CFG->wwwroot);
        die();

    }

    // Output page.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('verification_page', 'auth_twofactor'));
    echo html_writer::start_tag('br');
    $attributes = array('class' => 'alert alert-warning');
    echo html_writer::tag('div', get_string('noattemptsleft','auth_twofactor', minutes($remainingtime)), $attributes);
    echo $OUTPUT->continue_button($CFG->wwwroot);
    echo $OUTPUT->footer();
    die();

}

$mform  = new confirm_form();
$toform = array(
    'mid'       => $messageid,
    'ver'       => $code,
    'u'         => $u,
);

// If the form is cancel, return.
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot);
} else if ($fromform = $mform->get_data()) {

    if (!empty($fromform->phonenumber)) {
        update_user_phone($fromform->phonenumber, $fromform->u);
        die();
    }

    // Get the message content.
    $MessageBird = new \MessageBird\Client($accesskey);

    // try {
    //     $MessageResult = $MessageBird->messages->read($fromform->mid); // Set a message id here
    // } catch (\MessageBird\Exceptions\AuthenticateException $e) {
    //     // That means that your accessKey is unknown
    //     echo get_string('wronglogin', 'auth_twofactor');
    // } catch (\Exception $e) {
    //     var_dump($e->getMessage());
    // }

    // Validate against the message code, if this is true, redirect.
    if (base64_decode($fromform->ver) == $fromform->code) {
    // if ($MessageResult->body == $fromform->code) {

        // Get the user object.
        $user = json_decode(base64_decode($fromform->u));

        // Set url by default to go to the root.
        $url  = $CFG->wwwroot;

        // Complete the user login.
        if ($user) {
            complete_user_login($user);

            // Redirection
            if (user_not_fully_set_up($USER, true)) {
                $urltogo = $CFG->wwwroot.'/user/edit.php';
                redirect($urltogo);
            }

            // Validate the wantsurl.
            if (isset($SESSION->wantsurl)) {
                $url = $SESSION->wantsurl;
            }
        }

        redirect($url);

    } else {

        // The user has until X attempts to submit the form, before the timeout start counting.
        // He already did the first attempt so we must decrease the attempts.
        $attempts = !empty($SESSION->attempts) ? --$SESSION->attempts : $fromform->attempts;
        // var_dump($attempts);

        if ($attempts !== 0) {

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('enter_verification', 'auth_twofactor'));
            echo html_writer::start_tag('br');
            $attributes = array("class" => "alert alert-warning");
            echo html_writer::tag('div', get_string('incorrectcode','auth_twofactor', $attempts), $attributes);
            echo html_writer::start_tag('br');

            $SESSION->attempts = $attempts;
            $mform->display();
            echo $OUTPUT->footer();
            die();

        } else {

            // Timeout start counting, we set it in a $SESSION.
            $SESSION->timeout = get_config('auth_twofactor', 'timeout');
            $SESSION->lastactivity = time();

            // Unset attempts and mustattempt when the time starts counting.
            $SESSION->attempts = null;
            $SESSION->mustattempt = null;

            // Debug.
            // var_dump($SESSION->timeout); echo "<br>";
            // var_dump(date( 'm', $SESSION->lastactivity));

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('enter_verification', 'auth_twofactor'));
            echo html_writer::start_tag('br');
            $attributes = array("class" => "alert alert-warning");
            echo html_writer::tag('div', get_string('noattemptsleft','auth_twofactor', minutes($SESSION->timeout)), $attributes);
            echo $OUTPUT->continue_button($CFG->wwwroot);
            echo $OUTPUT->footer();
            die();

        }

    }

} else {

    if (empty($messageid) && empty($needphone)) {
        redirect($CFG->wwwroot);
        die();
    }

    // Display form the first time.
    $configattempts        = get_config('auth_twofactor', 'attempts');

    // Attempts are set to 0 the first time.
    $SESSION->attempts     = 0;

    // Let's setup this session to 1, just in case the user goes back to the login page
    // or access directly to the URL. This will help to redirect back to the confirm page.
    $SESSION->mustattempt = 1;

    // Let's carry over the messageid, to be able to redirect them to the confirm page
    // so they can continue using the code that was deliver to their phone to attempt.
    $SESSION->mid         = $messageid;
    $SESSION->ver         = $code;

    echo $OUTPUT->header();

    // Output is different for the phone and the verification code.
    if (!empty($needphone)) {
        $headingoutput = $OUTPUT->heading(get_string('enter_phone', 'auth_twofactor'));
    } else {
        $headingoutput = $OUTPUT->heading(get_string('enter_verification', 'auth_twofactor'));
    }
    echo $headingoutput;
    echo html_writer::start_tag('br');
    echo html_writer::start_tag('br');

    // ...else, display form.
    $toform['attempts'] = $configattempts;
    $mform->set_data($toform);
    $mform->display();
    echo $OUTPUT->footer();
    die();

}

/**
 * Update the user's phone number, and redirect to the main page again.
 *
 * @param  int    $phone
 * @param  string $user    User data comes enconded by default.
 * @return void
 */
function update_user_phone($phone, $user) {

    global $DB, $CFG;

    $user           = json_decode(base64_decode($user));
    $user->phone1   = $phone;

    if (!$DB->update_record('user', $user)) {
        echo get_string('phonenotupdated', 'auth_twofactor');
        die();
    }

    redirect($CFG->wwwroot);

}

/**
 * Format seconds to human readable format ex: x hours, x minutes, x seconds.
 *
 * @param  int $seconds
 * @return string
 */
function minutes($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds / 60) % 60);
    $seconds = $seconds % 60;
    return $hours > 0 ? "$hours hours, $minutes minutes" : ($minutes > 0 ? "$minutes minutes, $seconds seconds" : "$seconds seconds");
}
