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
 * Admin settings and defaults.
 *
 * @package auth_twofactor
 * @copyright  2017 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/authlib.php');

$settings->add(new admin_setting_configcheckbox('auth_twofactor/debug',
        new lang_string('debug', 'auth_twofactor'), new lang_string('debug_desc', 'auth_twofactor'), 0));

// Introductory explanation.
$settings->add(
    new admin_setting_heading(
        'auth_twofactor/pluginname',
        get_string('methodlist', 'auth_twofactor'),
        get_string('messagebirdnote', 'auth_twofactor')
    )
);

// Access key.
$settings->add(
    new admin_setting_configselect(
        'auth_twofactor/method',
        get_string('auth_twofactor_method', 'auth_twofactor'),
        get_string('auth_twofactor_method_desc', 'auth_twofactor'),
        0,
        array(
            'email'       => 'Email',
            'messagebird' => 'SMS (MessageBird)'
        )
    )
);

// Access key.
$settings->add(
    new admin_setting_configtext(
        'auth_twofactor/accesskey',
        get_string('auth_twofactor_accesskey', 'auth_twofactor'),
        get_string('auth_twofactor_accesskey_desc', 'auth_twofactor'), '',
        PARAM_RAW_TRIMMED
    )
);

// Originator.
$settings->add(
    new admin_setting_configtext(
        'auth_twofactor/sender',
        get_string('auth_twofactor_sender', 'auth_twofactor'),
        get_string('auth_twofactor_sender_desc', 'auth_twofactor'), '',
        PARAM_RAW
    )
);

// IP Range.
// $settings->add(new admin_setting_configtext('auth_twofactor/iprange', get_string('auth_twofactor_ip_range', 'auth_twofactor'),
//         get_string('auth_twofactor_ip_range_desc', 'auth_twofactor'), '', PARAM_RAW_TRIMMED));

// Introductory explanation.
$settings->add(new admin_setting_heading('auth_twofactor/headingverification', 'Verification Settings',
    ''));

// Timeout in seconds.
$settings->add(new admin_setting_configtext('auth_twofactor/timeout', get_string('auth_twofactor_timeout', 'auth_twofactor'),
        get_string('auth_twofactor_timeout_desc', 'auth_twofactor'), '', PARAM_RAW_TRIMMED));

// Number of attempts.
$settings->add(new admin_setting_configtext('auth_twofactor/attempts', get_string('auth_twofactor_attempts', 'auth_twofactor'),
        get_string('auth_twofactor_attempts_desc', 'auth_twofactor'), '', PARAM_RAW_TRIMMED));

// Timeout in seconds.
$settings->add(new admin_setting_configtext('auth_twofactor/timespan', get_string('auth_twofactor_timespan', 'auth_twofactor'),
        get_string('auth_twofactor_timespan_desc', 'auth_twofactor'), '', PARAM_RAW_TRIMMED));