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
 * shows an analysed view of a feedback on the mainsite
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

require_once("../../config.php");
require_once("lib.php");

$current_tab = 'analysis';

$id = required_param('id', PARAM_INT);  //the POST dominated the GET
$courseid = optional_param('courseid', false, PARAM_INT);

$url = new moodle_url('/mod/feedback/analysis_course.php', array('id'=>$id));
navigation_node::override_active_url($url);
if ($courseid !== false) {
    $url->param('courseid', $courseid);
}
$PAGE->set_url($url);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'feedback');
$context = context_module::instance($cm->id);

require_course_login($course, true, $cm);

$feedback = $PAGE->activityrecord;

if (!($feedback->publish_stats OR has_capability('mod/feedback:viewreports', $context))) {
    print_error('error');
}

$feedbackstructure = new mod_feedback_structure($feedback, $PAGE->cm, $courseid);

// Process course select form.
$courseselectform = new mod_feedback_course_select_form($url, $feedbackstructure);
if ($data = $courseselectform->get_data()) {
    redirect(new moodle_url($url, ['courseid' => $data->courseid]));
}

/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

$PAGE->set_heading($course->fullname);
$PAGE->set_title($feedback->name);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($feedback->name));

/// print the tabs
require('tabs.php');

//get the groupid
//lstgroupid is the choosen id
$mygroupid = false;

$courseselectform->display();

// Button "Export to excel".
if (has_capability('mod/feedback:viewreports', $context) && $feedbackstructure->get_items()) {
    echo $OUTPUT->container_start('form-buttons');
    $aurl = new moodle_url('/mod/feedback/analysis_to_excel.php',
        ['sesskey' => sesskey(), 'id' => $id, 'courseid' => (int)$courseid]);
    echo $OUTPUT->single_button($aurl, get_string('export_to_excel', 'feedback'));
    echo $OUTPUT->container_end();
}

// Show the summary.
$summary = new mod_feedback\output\summary($feedbackstructure);
echo $OUTPUT->render_from_template('mod_feedback/summary', $summary->export_for_template($OUTPUT));

// Print analysis of each question.
$analysis = new \mod_feedback\output\analysis($feedbackstructure, $mygroupid);
echo $OUTPUT->render_from_template('mod_feedback/analysis', $analysis->export_for_template($OUTPUT));

echo $OUTPUT->footer();

