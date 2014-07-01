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
 * Manage user profile fields.
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/profile/definelib.php');

admin_externalpage_setup('profilefields');

$action   = optional_param('action', '', PARAM_ALPHA);

$redirect = $CFG->wwwroot.'/user/profile/index.php';

$strchangessaved    = get_string('changessaved');
$strcancelled       = get_string('cancelled');
$strdefaultcategory = get_string('profiledefaultcategory', 'admin');
$strnofields        = get_string('profilenofieldsdefined', 'admin');
$strcreatefield     = get_string('profilecreatefield', 'admin');


// Do we have any actions to perform before printing the header.

switch ($action) {
    case 'movecategory':
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);

        if (confirm_sesskey()) {
            profile_move_category($id, $dir);
        }
        redirect($redirect);
        break;
    case 'movefield':
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);

        if (confirm_sesskey()) {
            profile_move_field($id, $dir);
        }
        redirect($redirect);
        break;
    case 'deletecategory':
        $id      = required_param('id', PARAM_INT);
        if (confirm_sesskey()) {
            profile_delete_category($id);
        }
        redirect($redirect, get_string('deleted'));
        break;
    case 'deletefield':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);

        // If no userdata for profile than don't show confirmation.
        $datacount = $DB->count_records('user_info_data', array('fieldid' => $id));
        if (((data_submitted() and $confirm) or ($datacount === 0)) and confirm_sesskey()) {
            profile_delete_field($id);
            redirect($redirect, get_string('deleted'));
        }

        // Ask for confirmation, as there is user data available for field.
        $fieldname = $DB->get_field('user_info_field', 'name', array('id' => $id));
        $optionsyes = array ('id' => $id, 'confirm' => 1, 'action' => 'deletefield', 'sesskey' => sesskey());
        $strheading = get_string('profiledeletefield', 'admin', $fieldname);
        $PAGE->navbar->add($strheading);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $formcontinue = new single_button(new moodle_url($redirect, $optionsyes), get_string('yes'), 'post');
        $formcancel = new single_button(new moodle_url($redirect), get_string('no'), 'get');
        echo $OUTPUT->confirm(get_string('profileconfirmfielddeletion', 'admin', $datacount), $formcontinue, $formcancel);
        echo $OUTPUT->footer();
        die;
        break;
    case 'editfield':
        $id       = optional_param('id', 0, PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);

        profile_edit_field($id, $datatype, $redirect);
        die;
        break;
    case 'editcategory':
        $id = optional_param('id', 0, PARAM_INT);

        profile_edit_category($id, $redirect);
        die;
        break;
    default:
        // Normal form.
}

// Show all categories.
$categories = $DB->get_records('user_info_category', null, 'sortorder ASC');

// Check that we have at least one category defined.
if (empty($categories)) {
    $defaultcategory = new stdClass();
    $defaultcategory->name = $strdefaultcategory;
    $defaultcategory->sortorder = 1;
    $DB->insert_record('user_info_category', $defaultcategory);
    redirect($redirect);
}

// Print the header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('profilefields', 'admin'));

$fields = $DB->get_records('user_info_field', array(), 'sortorder ASC');
echo $PAGE->get_renderer('user')->user_profile_fields_overview($categories, $fields);

echo '<hr />';
echo '<div class="profileeditor">';

// Create a new field link.
$options = profile_list_datatypes();
$popupurl = new moodle_url('/user/profile/index.php?id=0&action=editfield');
echo $OUTPUT->single_select($popupurl, 'datatype', $options, '', array('' => $strcreatefield), 'newfieldform');

// Add a div with a class so themers can hide, style or reposition the text.
html_writer::start_tag('div', array('class' => 'adminuseractionhint'));
echo get_string('or', 'lesson');
html_writer::end_tag('div');

// Create a new category link.
$options = array('action' => 'editcategory');
echo $OUTPUT->single_button(new moodle_url('index.php', $options), get_string('profilecreatecategory', 'admin'));

echo '</div>';

echo $OUTPUT->footer();
die;

