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

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->libdir}/formslib.php");

class confirm_form extends moodleform {

    public function definition() {

        global $DB;

        $mform = & $this->_form;

        // Hidden fields.
        $mform->addElement('hidden', 'ver');
        $mform->setType('ver', PARAM_NOTAGS);

        $mform->addElement('hidden', 'mid');
        $mform->setType('mid', PARAM_NOTAGS);

        $mform->addElement('hidden', 'u');
        $mform->setType('u', PARAM_NOTAGS);

        $mform->addElement('hidden', 'secret');
        $mform->setType('secret', PARAM_NOTAGS);

        // Input for confirmation message.
        $mform->addElement('text', 'code', get_string('verificationcode', 'auth_twofactor'));
        $mform->setType('code', PARAM_NOTAGS);
        $mform->addRule('code', get_string('required'), 'required', null,  'server');

        // Action buttons.
        $this->add_action_buttons(true, get_string('confirm'));
    }

}