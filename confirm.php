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
require('vendor/autoload.php');

// Get configs.
$accesskey         = get_config('auth_twofactor', 'accesskey');
$debug             = get_config('auth_twofactor', 'debug');
$SESSION->timespan = get_config('auth_twofactor', 'timespan');

// Get url params.
$code      = optional_param('ver', "", PARAM_NOTAGS);
$messageid = optional_param('mid', "", PARAM_NOTAGS);
$istimeout = optional_param('timeout', 0, PARAM_NOTAGS);
$u         = $SESSION->u;

$debugcode = ( !empty($code) ) ? html_writer::tag('div', base64_decode($code), array("class" => "alert alert-success")) : "";

global $DB, $OUTPUT, $PAGE, $USER, $CFG, $SESSION;

// Set page layout and headings.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('popup');
$PAGE->set_url(new moodle_url('/auth/twofactor/confirm.php'));
$PAGE->set_title(get_string('verificationcode', 'auth_twofactor'));
$PAGE->set_heading(get_string('verificationcode', 'auth_twofactor'));

check_timespan();

// If the timeout time passed, then take the user to the home page.
check_timeout($istimeout);

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
    }

    if (!$debug) {

        // Get the message content.
        $messagebird = new \MessageBird\Client($accesskey);
        $comparison  = false;

        try {
            $messageresult = $messagebird->messages->read($fromform->mid); // ...Set a message id here.
            $comparison = ($messageresult->body == $fromform->code);
        } catch (\MessageBird\Exceptions\AuthenticateException $e) {
            // That means that your accessKey is unknown.
            $attributes = array("class" => "alert alert-warning");
            echo html_writer::tag('div', get_string('wronglogin', 'auth_twofactor'), $attributes);
        } catch (\Exception $e) {

            if ($debug) {
                var_dump($e->getMessage());
            }

        }

    } else {
        $comparison = (base64_decode($fromform->ver) == $fromform->code);
    }

    // Validate against the message code, if this is true, redirect.
    if ($comparison) {

        // If the user login successfully, we can remove this session.
        unset($SESSION->mustattempt);

        // Get the user object.
        $user = json_decode(base64_decode($fromform->u));

        // Set url by default to go to the root.
        $url  = $CFG->wwwroot;

        // Complete the user login.
        if ($user) {
            complete_user_login($user);

            // Redirection.
            if (user_not_fully_set_up($USER, true)) {
                $urltogo = $CFG->wwwroot.'/user/edit.php';
                redirect($urltogo);
            }

            // Validate the wantsurl.
            if (isset($SESSION->wantsurl)) {
                $url = $SESSION->wantsurl;
            }
        }

        // Redirect to the desired url.
        redirect($url);

    } else {

        $configattempts = get_config('auth_twofactor', 'attempts');

        // The user has until X attempts to submit the form, before the timeout start counting.
        // He already did the first attempt so we must decrease the attempts.
        $attempts = !empty($SESSION->attempts) ? --$SESSION->attempts : $configattempts;

        if ($attempts !== 0) {

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('enter_verification', 'auth_twofactor'));
            echo html_writer::start_tag('br');

            echo $debugcode;

            $attributes = array("class" => "alert alert-warning");
            echo html_writer::tag('div', get_string('incorrectcode', 'auth_twofactor', $attempts), $attributes);
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
            unset($SESSION->attempts);
            unset($SESSION->mustattempt);

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('enter_verification', 'auth_twofactor'));
            echo html_writer::start_tag('br');

            // DELETE THE FOLLOWING TWO LINES, THIS IS ONLY FOR TESTING PURPOSES.
            echo $debugcode;

            $attributes = array("class" => "alert alert-warning");
            echo html_writer::tag('div', get_string('noattemptsleft', 'auth_twofactor', minutes($SESSION->timeout)), $attributes);
            echo $OUTPUT->continue_button($CFG->wwwroot);
            echo $OUTPUT->footer();
            die();

        }

    }

} else {

    if (empty($messageid) && !$debug) {
        unset($SESSION->attempts);
        unset($SESSION->mustattempt);
        redirect($CFG->wwwroot);
    }

    if ($debug && empty($code)) {
        unset($SESSION->attempts);
        unset($SESSION->mustattempt);
        redirect($CFG->wwwroot);
    }

    // Activity time will help to validate the time for the timespan.
    $SESSION->timespan_lastactivity = time();

    // Attempts are set to 0 the first time.
    $SESSION->attempts     = 0;

    // Let's setup this session to 1, just in case the user goes back to the login page
    // or access directly to the URL. This will help to redirect back to the confirm page.
    if (isset($SESSION->justloggedin) && $SESSION->justloggedin) {
        unset($SESSION->mustattempt);
    } else {
        $SESSION->mustattempt = 1;
    }

    unset($SESSION->fromurl);

    // Let's carry over the messageid, to be able to redirect them to the confirm page
    // so they can continue using the code that was deliver to their phone to attempt.
    $SESSION->mid         = $messageid;
    $SESSION->ver         = $code;
    $timespan             = $SESSION->timespan;

    echo $OUTPUT->header();

    // Output is different for the phone and the verification code.
    echo $OUTPUT->heading(get_string('enter_verification', 'auth_twofactor'));
    echo html_writer::start_tag('br');

    // DELETE THE FOLLOWING TWO LINES, THIS IS ONLY FOR TESTING PURPOSES.
    echo $debugcode;
    echo ( !empty($timespan) ) ? html_writer::tag('div', get_string('validduringtimespan', 'auth_twofactor', minutes($timespan)), array("class" => "alert alert-warning")) : "";

    echo html_writer::start_tag('br');

    // ...else, display form.
    $mform->set_data($toform);
    $mform->display();
    echo $OUTPUT->footer();
    die();

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

/**
 * Validates if the timeout expires and display the validation page until
 * the timeout is expired.
 *
 * @param  string $istimeout
 * @return void
 */
function check_timeout($istimeout) {

    global $SESSION, $CFG, $OUTPUT;

    if (empty($istimeout)) {
        return;
    }

    // If the timeout expired, destroy the sessions and redirect to the login.
    // They should be able to try again.
    $remainingtime = $SESSION->timeout - (time() - $SESSION->lastactivity);
    if (time() - $SESSION->lastactivity >= $SESSION->timeout) {

        // Unset sessions.
        unset($SESSION->lastactivity);
        unset($SESSION->timeout);
        redirect($CFG->wwwroot);

    }

    // Output page.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('verification_page', 'auth_twofactor'));
    echo html_writer::start_tag('br');
    $attributes = array('class' => 'alert alert-warning');
    echo html_writer::tag('div', get_string('noattemptsleft', 'auth_twofactor', minutes($remainingtime)), $attributes);
    echo $OUTPUT->continue_button($CFG->wwwroot);
    echo $OUTPUT->footer();
    die();

}

/**
 * This function makes sure that the verification code is only valid
 * for X amount of time.
 *
 * @return void
 */
function check_timespan() {

    global $SESSION, $CFG;

    $timespan = get_config('auth_twofactor', 'timespan');

    if (empty($timespan) || empty($SESSION->timespan_lastactivity)) {
        return;
    }

    if (time() - $SESSION->timespan_lastactivity >= $SESSION->timespan) {
        // Unset sessions.
        unset($SESSION->timespan_lastactivity);
        unset($SESSION->timespan);
        unset($SESSION->lastactivity);
        unset($SESSION->timeout);
        unset($SESSION->mustattempt);
        redirect($CFG->wwwroot);
    }

}
