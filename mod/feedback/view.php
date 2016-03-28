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
 * the first page to view the feedback
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/feedback/lib.php');
require_once($CFG->dirroot . '/mod/feedback/locallib.php');

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);

$current_tab = 'view';

list($course, $cm) = get_course_and_cm_from_cmid($id, 'feedback');
require_course_login($course, true, $cm);
$feedback = $PAGE->activityrecord;

$feedbackstructure = new mod_feedback_completion($feedback, $cm, $courseid);

$context = context_module::instance($cm->id);

$feedback_complete_cap = $feedbackstructure->can_complete();

$courseid = $feedbackstructure->get_courseid();

if ($course->id == SITEID) {
    $PAGE->set_pagelayout('incourse');
}
$PAGE->set_url('/mod/feedback/view.php', array('id' => $cm->id));
$PAGE->set_title($feedback->name);
$PAGE->set_heading($course->fullname);

// Check whether the feedback is mapped to the given courseid.
if ($course->id == SITEID AND !has_capability('mod/feedback:edititems', $context)) {
    if ($DB->get_records('feedback_sitecourse_map', array('feedbackid' => $feedback->id))) {
        $params = array('feedbackid' => $feedback->id, 'courseid' => $courseid);
        if (!$DB->get_record('feedback_sitecourse_map', $params)) {
            if ($courseid == SITEID) {
                echo $OUTPUT->header();
                echo $OUTPUT->notification(get_string('cannotaccess', 'mod_feedback'));
                echo $OUTPUT->footer();
                exit;
            } else {
                print_error('invalidcoursemodule');
            }
        }
    }
}

//check whether the given courseid exists
if ($courseid AND $courseid != SITEID) {
    if ($course2 = $DB->get_record('course', array('id'=>$courseid))) {
        require_course_login($course2); //this overwrites the object $course :-(
    } else {
        print_error('invalidcourseid');
    }
}

// Trigger module viewed event.
$event = \mod_feedback\event\course_module_viewed::create(array(
    'objectid' => $feedback->id,
    'context' => $context,
    'anonymous' => ($feedback->anonymous == FEEDBACK_ANONYMOUS_YES),
    'other' => array(
        'anonymous' => $feedback->anonymous // Deprecated.
    )
));
//$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('feedback', $feedback);
$event->trigger();

/// Print the page header
echo $OUTPUT->header();

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

$previewimg = $OUTPUT->pix_icon('t/preview', get_string('preview'));
$previewlnk = new moodle_url('/mod/feedback/print.php', array('id' => $id));
if ($courseid) {
    $previewlnk->param('courseid', $courseid);
}
$preview = html_writer::link($previewlnk, $previewimg);

echo $OUTPUT->heading(format_string($feedback->name) . $preview);

// Print the tabs.
require('tabs.php');

// Show description.
echo $OUTPUT->box_start('generalbox feedback_description');
$options = (object)array('noclean' => true);
echo format_module_intro('feedback', $feedback, $cm->id);
echo $OUTPUT->box_end();

//show some infos to the feedback
if (has_capability('mod/feedback:edititems', $context)) {

    echo $OUTPUT->heading(get_string('overview', 'feedback'), 3);

    //get the groupid
    $groupselect = groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/feedback/view.php?id='.$cm->id, true);
    $mygroupid = groups_get_activity_group($cm);

    echo $OUTPUT->box_start('boxaligncenter');
    echo $groupselect.'<div class="clearer">&nbsp;</div>';
    $completedscount = feedback_get_completeds_group_count($feedback, $mygroupid);
    echo $OUTPUT->box_start('feedback_info');
    echo '<span class="feedback_info">';
    echo get_string('completed_feedbacks', 'feedback').': ';
    echo '</span>';
    echo '<span class="feedback_info_value">';
    echo $completedscount;
    echo '</span>';
    echo $OUTPUT->box_end();

    $params = array('feedback'=>$feedback->id, 'hasvalue'=>1);
    $itemscount = $DB->count_records('feedback_item', $params);
    echo $OUTPUT->box_start('feedback_info');
    echo '<span class="feedback_info">';
    echo get_string('questions', 'feedback').': ';
    echo '</span>';
    echo '<span class="feedback_info_value">';
    echo $itemscount;
    echo '</span>';
    echo $OUTPUT->box_end();

    if ($feedback->timeopen) {
        echo $OUTPUT->box_start('feedback_info');
        echo '<span class="feedback_info">';
        echo get_string('feedbackopen', 'feedback').': ';
        echo '</span>';
        echo '<span class="feedback_info_value">';
        echo userdate($feedback->timeopen);
        echo '</span>';
        echo $OUTPUT->box_end();
    }
    if ($feedback->timeclose) {
        echo $OUTPUT->box_start('feedback_info');
        echo '<span class="feedback_info">';
        echo get_string('feedbackclose', 'feedback').': ';
        echo '</span>';
        echo '<span class="feedback_info_value">';
        echo userdate($feedback->timeclose);
        echo '</span>';
        echo $OUTPUT->box_end();
    }
    echo $OUTPUT->box_end();
}

if (has_capability('mod/feedback:edititems', $context)) {
    require_once($CFG->libdir . '/filelib.php');

    echo $OUTPUT->heading(get_string("page_after_submit", "feedback"), 3);
    echo $OUTPUT->box($feedbackstructure->page_after_submit(), 'generalbox feedback_after_submit');
}

if (!has_capability('mod/feedback:viewreports', $context) &&
        feedback_can_view_analysis($feedback, $context, $courseid)) {
    $url_params = array('id' => $id, 'courseid' => $courseid);
    $analysisurl = new moodle_url('/mod/feedback/analysis.php', $url_params);
    echo '<div class="mdl-align"><a href="'.$analysisurl->out().'">';
    echo get_string('completed_feedbacks', 'feedback').'</a>';
    echo '</div>';
}

//####### mapcourse-start
if (has_capability('mod/feedback:mapcourse', $context) && $feedback->course == SITEID) {
    echo $OUTPUT->box_start('generalbox feedback_mapped_courses');
    echo $OUTPUT->heading(get_string("mappedcourses", "feedback"), 3);
    $mappedcourses = feedback_get_courses_from_sitecourse_map($feedback->id);
    echo '<p>' . get_string('mapcourse_help', 'feedback') . '</p>';
    $mapurl = new moodle_url('/mod/feedback/mapcourse.php', array('id' => $id));
    echo '<p class="mdl-align">' . html_writer::link($mapurl, get_string('mapcourses', 'feedback')) . '</p>';
    echo $OUTPUT->box_end();
}
//####### mapcourse-end

//####### completed-start
if ($feedback_complete_cap) {
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    //check, whether the feedback is open (timeopen, timeclose)
    $checktime = time();
    if (($feedback->timeopen > $checktime) OR
            ($feedback->timeclose < $checktime AND $feedback->timeclose > 0)) {

        echo $OUTPUT->notification(get_string('feedback_is_not_open', 'feedback'));
        echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    }

    if ($feedbackstructure->can_submit()) {
        //if the user is not known so we cannot save the values temporarly
        $completeurl = new moodle_url('/mod/feedback/complete.php',
                ['id' => $id, 'courseid' => $courseid]);
        if ($startpage = $feedbackstructure->get_resume_page()) {
            $completeurl->param('gopage', $startpage);
            $label = get_string('continue_the_form', 'feedback');
        } else {
            $label = get_string('complete_the_form', 'feedback');
        }
        echo html_writer::link($completeurl, $label);
    } else {
        echo $OUTPUT->notification(get_string('this_feedback_is_already_submitted', 'feedback'));
        $OUTPUT->continue_button(course_get_url($courseid ?: $course->id));
    }
    echo $OUTPUT->box_end();
}
//####### completed-end

/// Finish the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

echo $OUTPUT->footer();

