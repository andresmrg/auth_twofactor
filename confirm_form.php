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
 * Generates the form to confirm a class
 *
 * @package     auth_twofactor
 * @author      Andres Ramos <andres.ramos@lmsdoctor.com>
 * @copyright   LMS Doctor
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");

class confirm_form extends moodleform {

    function definition() {

        global $DB;

        $mform = & $this->_form;

        $needphone = optional_param('phone', 0, PARAM_INT);

        // Hidden fields
        $mform->addElement('hidden', 'ver');
        $mform->setType('ver', PARAM_NOTAGS);

        $mform->addElement('hidden', 'mid');
        $mform->setType('mid', PARAM_NOTAGS);

        $mform->addElement('hidden', 'u');
        $mform->setType('u', PARAM_NOTAGS);

        $mform->addElement('hidden', 'attempts');
        $mform->setType('attempts', PARAM_INT);

        if (empty($needphone)) {
            // Input for confirmation message.
            $mform->addElement('text','code', get_string('verificationcode', 'auth_twofactor'));
            $mform->setType('code', PARAM_NOTAGS);
        } else {
            // Input to add the phone number.
            $conditions = array('placeholder' => get_string('phone_example', 'auth_twofactor'));
            $mform->addElement('text','phonenumber', get_string('phone', 'auth_twofactor'), $conditions);
            $mform->setType('phonenumber', PARAM_RAW);
        }

        // Action buttons.
        $this->add_action_buttons(true, get_string('confirm'));
    }

    function get_data(){
        global $DB;

        $data = parent::get_data();

        if (!empty($data)) {
            $mform =& $this->_form;

            if(!empty($mform->_submitValues['phonenumber'])) {
                $data->phonenumber = $mform->_submitValues['phonenumber'];
            }

            // if(!empty($mform->_submitValues['u'])) {
            //     $data->u = $mform->_submitValues['u'];
            // }

        }

        return $data;
    }

}