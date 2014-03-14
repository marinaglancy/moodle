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
 * prints the form to confirm the deleting of a completed
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

require_once("../../config.php");
require_once("lib.php");
require_once('delete_completed_form.php');

$cmid = required_param('id', PARAM_INT);
$completedid = optional_param('completedid', 0, PARAM_INT);
$return = optional_param('return', 'entries', PARAM_ALPHA);

if ($completedid == 0) {
    print_error('no_complete_to_delete',
                'feedback',
                'show_entries.php?id='.$cmid.'&do_show=showentries');
}

$PAGE->set_url('/mod/feedback/delete_completed.php', array('id'=>$cmid, 'completed'=>$completedid));

list($context, $course, $cm) = $PAGE->login_to_cm('feedback', $cmid);
require_capability('mod/feedback:deletesubmissions', $context);

$mform = new mod_feedback_delete_completed_form();
$newformdata = array('id'=>$cmid,
                    'completedid'=>$completedid,
                    'confirmdelete'=>'1',
                    'do_show'=>'edit',
                    'return'=>$return);
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    if ($return == 'entriesanonym') {
        redirect('show_entries_anonym.php?id='.$cmid);
    } else {
        redirect('show_entries.php?id='.$cmid.'&do_show=showentries');
    }
}

if (isset($formdata->confirmdelete) AND $formdata->confirmdelete == 1) {
    if ($completed = $DB->get_record('feedback_completed', array('id'=>$completedid))) {
        feedback_delete_completed($completedid);
        if ($return == 'entriesanonym') {
            redirect('show_entries_anonym.php?id='.$cmid);
        } else {
            redirect('show_entries.php?id='.$cmid.'&do_show=showentries');
        }
    }
}

/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

$PAGE->navbar->add(get_string('delete_entry', 'feedback'));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($cm->name);
echo $OUTPUT->header();

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
echo $OUTPUT->heading($cm->get_formatted_name());
echo $OUTPUT->box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
echo html_writer::tag('p', get_string('confirmdeleteentry', 'feedback'), array('class' => 'bold'));
$mform->display();
echo $OUTPUT->box_end();


echo $OUTPUT->footer();
