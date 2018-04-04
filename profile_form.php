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
 * Generates the form to update the phone number.
 *
 * @package     auth_twofactor
 * @author      Andres Ramos <andres.ramos@lmsdoctor.com>
 * @copyright   LMS Doctor
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->libdir}/formslib.php");

class profile_form extends moodleform {

    public function definition() {

        global $DB;

        $mform = & $this->_form;

        $mform->addElement('hidden', 'u');
        $mform->setType('u', PARAM_NOTAGS);

        // Input to add the phone number.
        $conditions = array('placeholder' => get_string('phone_example', 'auth_twofactor'));
        $mform->addElement('text', 'phonenumber', get_string('phone', 'auth_twofactor'), $conditions);
        $mform->setType('phonenumber', PARAM_RAW);

        // Action buttons.
        $this->add_action_buttons(false, get_string('confirm'));
    }

}