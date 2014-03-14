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
 * deletes an item of the feedback
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

require_once("../../config.php");
require_once("lib.php");
require_once('delete_item_form.php');

$cmid = required_param('id', PARAM_INT);
$deleteitem = required_param('deleteitem', PARAM_INT);

$PAGE->set_url('/mod/feedback/delete_item.php', array('id'=>$cmid, 'deleteitem'=>$deleteitem));
list($context, $course, $cm) = $PAGE->login_to_cm('feedback', $cmid);

require_capability('mod/feedback:edititems', $context);

$mform = new mod_feedback_delete_item_form();
$newformdata = array('id'=>$cmid,
                    'deleteitem'=>$deleteitem,
                    'confirmdelete'=>'1');
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect('edit.php?id='.$cmid);
}

if (isset($formdata->confirmdelete) AND $formdata->confirmdelete == 1) {
    feedback_delete_item($formdata->deleteitem);
    redirect('edit.php?id=' . $cmid);
}


/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

$PAGE->navbar->add(get_string('delete_item', 'feedback'));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($cm->name);
echo $OUTPUT->header();

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
echo $OUTPUT->heading($cm->get_formatted_name());
echo $OUTPUT->box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
echo html_writer::tag('p', get_string('confirmdeleteitem', 'feedback'), array('class' => 'bold'));
print_string('relateditemsdeleted', 'feedback');
$mform->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();


