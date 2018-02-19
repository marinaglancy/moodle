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
 * Verify age and location (digital minor check).
 *
 * @package     core
 * @subpackage auth
 * @copyright   2018 Mihail Geshoski <mihail@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once('lib.php');

$authplugin = signup_is_enabled();
if (!$authplugin || !is_age_digital_consent_verification_enabled()) {
    print_error('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/login/verify_age_location.php'));

if (isloggedin() and !isguestuser()) {
    // Prevent signing up when already logged in.
    echo $OUTPUT->header();
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url($CFG->httpswwwroot . '/login/logout.php',
        array('sesskey' => sesskey(), 'loginpage' => 1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url('/'), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('cannotsignup', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

if (!\core_auth\session\signup::is_set()) { // Signup session does not exists.
    \core_auth\session\signup::create();
} else { // Signup session exists.
    if (!\core_auth\session\signup::is_valid()) { // Signup session is no longer valid.
        \core_auth\session\signup::destroy();
        redirect(new moodle_url('/login/index.php'));
    }
    // Handle if verification of age and location (minor check) has already been done.
    if (\core_auth\session\signup::is_set_minor_status()) {
        $isminor = \core_auth\session\signup::get_minor_status();
        if ($isminor) { // The user that attempts to sign up is a digital minor.
            redirect(new moodle_url('/login/digital_minor.php'));
        } else { // The user that attempts to sign up is not a digital minor.
            redirect(new moodle_url('/login/signup.php'));
        }
    }
}

$PAGE->navbar->add(get_string('login'));
$PAGE->navbar->add(get_string('agelocationverification'));

$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('agelocationverification'));
$PAGE->set_heading($SITE->fullname);

$mform = new \core_auth\form\verify_age_location_form();
$page = new \core_auth\output\verify_age_location_page($mform);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/login/index.php'));
} else if ($data = $mform->get_data()) {
    try {
        $isminor = core_login_is_minor($data->age, $data->country);
        \core_auth\session\signup::set_minor_status($isminor);
        if ($isminor) {
            redirect(new moodle_url('/login/digital_minor.php'));
        } else {
            redirect(new moodle_url('/login/signup.php'));
        }
    } catch (moodle_exception $e) {
        // Display a user-friendly error message.
        $errormessage = get_string('couldnotverifyagedigitalconsent', 'error');
        $page = new \core_auth\output\verify_age_location_page($mform, $errormessage);
        echo $OUTPUT->header();
        echo $OUTPUT->render($page);
        echo $OUTPUT->footer();
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->render($page);
    echo $OUTPUT->footer();
}
