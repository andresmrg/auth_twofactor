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
require_once('profile_form.php');
require('vendor/autoload.php');

global $DB, $OUTPUT, $PAGE, $USER, $CFG, $SESSION;

// Get url params.
$u = $SESSION->u;

// Set page layout and headings.
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('popup');
$PAGE->set_url(new moodle_url('/auth/twofactor/profile.php'));
$PAGE->set_title(get_string('enter_phone', 'auth_twofactor'));
$PAGE->set_heading(get_string('enter_phone', 'auth_twofactor'));

$mform  = new profile_form();
$toform = array(
    'u'         => $u,
);

// If the form is cancel, return.
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot);
} else if ($fromform = $mform->get_data()) {

    if (!empty($fromform->phonenumber)) {
        update_user_phone($fromform->phonenumber, $fromform->u);
    }

} else {

    echo $OUTPUT->header();

    // Output is different for the phone and the verification code.
    echo $OUTPUT->heading(get_string('enter_phone', 'auth_twofactor'));
    echo html_writer::start_tag('br');
    echo html_writer::start_tag('br');

    // ...else, display form.
    $mform->set_data($toform);
    $mform->display();
    echo $OUTPUT->footer();

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

    if (isset($SESSION->mustattempt)) {
        unset($SESSION->mustattempt);
    }

    $user           = json_decode(base64_decode($user));
    $user->phone2   = $phone;

    $DB->update_record('user', $user);

    redirect('/login/index.php', get_string('phoneupdatesuccess', 'auth_twofactor'), 5, \core\output\notification::NOTIFY_SUCCESS);

}
