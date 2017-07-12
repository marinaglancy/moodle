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

/*
 * @package    moodle
 * @subpackage registration
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * This page displays the site registration form for Moodle.net.
 * It handles redirection to the hub to continue the registration workflow process.
 * It also handles update operation by web service.
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/registration/forms.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/registration/lib.php');

admin_externalpage_setup('registrationmoodleorg');

$unregistration = optional_param('unregistration', 0, PARAM_INT);

$registrationmanager = new registration_manager();
$registeredhub = $registrationmanager->get_registeredhub();

if ($unregistration && $registeredhub) {
    $siteunregistrationform = new site_unregistration_form();

    if ($siteunregistrationform->is_cancelled()) {
        redirect(new moodle_url('/admin/registration/index.php'));
    } else if ($data = $siteunregistrationform->get_data()) {
        if ($registrationmanager->unregister($data->unpublishalladvertisedcourses,
            $data->unpublishalluploadedcourses)) {
            redirect(new moodle_url('/admin/registration/index.php'));
        }
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('unregisterfrom', 'hub', 'Moodle.net'), 3, 'main');
    $siteunregistrationform->display();
    echo $OUTPUT->footer();
    exit;
}


$siteregistrationform = new site_registration_form();
if ($fromform = $siteregistrationform->get_data()) {

    // Save the settings.
    $cleanhuburl = clean_param(HUB_MOODLEORGHUBURL, PARAM_ALPHANUMEXT);
    foreach (registration_manager::FORM_FIELDS as $field) {
        set_config('site_'.$field.'_' . $cleanhuburl, $fromform->$field, 'hub');
    }

    if ($registeredhub) {
        try {
            $registrationmanager->update_registration($registeredhub);
        } catch (Exception $e) {
            redirect(new moodle_url('/admin/registration/index.php'),
                get_string('errorregistration', 'hub', $e->getMessage()), 0,
                \core\output\notification::NOTIFY_ERROR);
        }

        redirect(new moodle_url('/admin/registration/index.php'), get_string('siteregistrationupdated', 'hub'), 0,
            \core\output\notification::NOTIFY_SUCCESS);

    } else {
        $registrationmanager->register();
        // This method will redirect away.
    }

}

/////// OUTPUT SECTION /////////////

echo $OUTPUT->header();

// Current status of registration on Moodle.net.

$notificationtype = \core\output\notification::NOTIFY_ERROR;
if ($registeredhub) {
    if ($registeredhub->timemodified == 0) {
        $registrationmessage = get_string('pleaserefreshregistrationunknown', 'admin');
    } else {
        $lastupdated = userdate($registeredhub->timemodified, get_string('strftimedate', 'langconfig'));
        $registrationmessage = get_string('pleaserefreshregistration', 'admin', $lastupdated);
        $notificationtype = \core\output\notification::NOTIFY_INFO;
    }
    echo $OUTPUT->notification($registrationmessage, $notificationtype);
} else {
    $registrationmessage = get_string('registrationwarning', 'admin');
    echo $OUTPUT->notification($registrationmessage, $notificationtype);
}

// Unregister button and heading.
if ($registeredhub) {

    $unregisterhuburl = new moodle_url("/admin/registration/index.php", ['unregistration' => 1]);
    $unregisterbutton = new single_button($unregisterhuburl, get_string('unregister', 'hub'));

    echo $OUTPUT->render($unregisterbutton);

    echo $OUTPUT->heading(get_string('updatesite', 'hub', $registeredhub->hubname));

} else {
    echo $OUTPUT->heading(get_string('registerwithmoodleorg', 'admin'));
}

$renderer = $PAGE->get_renderer('core', 'register');
echo $renderer->moodleorg_registration_message();

$siteregistrationform->display();
echo $OUTPUT->footer();
