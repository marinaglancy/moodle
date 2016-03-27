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
 * prints the form so the user can fill out the feedback
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

require_once("../../config.php");
require_once("lib.php");
require_once("locallib.php");
require_once($CFG->libdir . '/completionlib.php');

feedback_init_feedback_session();

$id = required_param('id', PARAM_INT);
$completedid = optional_param('completedid', false, PARAM_INT);
$preservevalues  = optional_param('preservevalues', 0,  PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$gopage = optional_param('gopage', 0, PARAM_INT);
$lastpage = optional_param('lastpage', false, PARAM_INT);
$startitempos = optional_param('startitempos', 0, PARAM_INT);
$lastitempos = optional_param('lastitempos', 0, PARAM_INT);

$highlightrequired = false;

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

list($course, $cm) = get_course_and_cm_from_cmid($id, 'feedback');
$feedback = $DB->get_record("feedback", array("id" => $cm->instance), '*', MUST_EXIST);

$context = context_module::instance($cm->id);

$feedback_complete_cap = false;

if (has_capability('mod/feedback:complete', $context)) {
    $feedback_complete_cap = true;
}

if (!empty($CFG->feedback_allowfullanonymous)
        AND $course->id == SITEID
        AND $feedback->anonymous == FEEDBACK_ANONYMOUS_YES
        AND (!isloggedin() OR isguestuser())) {
    // Guests are allowed to complete fully anonymous feedback without having 'mod/feedback:complete' capability.
    $feedback_complete_cap = true;
}

//check whether the feedback is located and! started from the mainsite
if ($course->id != SITEID) {
    // Feedbacks that are not on front page do not allow to specify courseid.
    $courseid = null;
} else if (!$courseid) {
    $courseid = SITEID;
}

//check whether the feedback is mapped to the given courseid
if ($course->id == SITEID AND !has_capability('mod/feedback:edititems', $context)) {
    if ($DB->get_records('feedback_sitecourse_map', array('feedbackid'=>$feedback->id))) {
        $params = array('feedbackid'=>$feedback->id, 'courseid'=>$courseid);
        if (!$DB->get_record('feedback_sitecourse_map', $params)) {
            print_error('notavailable', 'feedback');
        }
    }
}

$urlparams = array('id' => $cm->id, 'gopage' => $gopage, 'courseid' => $courseid);
$PAGE->set_url('/mod/feedback/complete.php', $urlparams);

require_course_login($course, true, $cm);
$PAGE->set_activity_record($feedback);

$feedbackstructure = new mod_feedback_structure($feedback, $cm, $courseid);
$feedbackcompletion = new mod_feedback_completion($feedbackstructure);

//check whether the given courseid exists
if ($courseid AND $courseid != SITEID) {
    if ($course2 = $DB->get_record('course', array('id'=>$courseid))) {
        require_course_login($course2); //this overwrites the object $course :-(
        $course = $DB->get_record("course", array("id"=>$cm->course)); // the workaround
    } else {
        print_error('invalidcourseid');
    }
}

if (!$feedback_complete_cap) {
    print_error('error');
}

$PAGE->navbar->add(get_string('feedback:complete', 'feedback'));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($feedback->name);

if ($course->id == SITEID) {
    $PAGE->set_cm($cm, $course); // set's up global $COURSE
    $PAGE->set_pagelayout('incourse');
}

//check, if the feedback is open (timeopen, timeclose)
if (!$feedbackstructure->is_open()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($feedback->name));
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification(get_string('feedback_is_not_open', 'feedback'));
    echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

// Mark activity viewed for completion-tracking
$completion = new completion_info($course);
if (isloggedin() && !isguestuser()) {
    $completion->set_module_viewed($cm);
}

// Check if user is prevented from re-submission.
$cansubmit = $feedbackstructure->can_submit();

// Initialise the form processing feedback completion.
if (!$feedbackstructure->is_empty() && $cansubmit) {
    $form = new mod_feedback_complete_form(mod_feedback_complete_form::MODE_COMPLETE, 
            $feedbackstructure, 'feedback_complete_form', array('gopage' => $gopage));
    if ($form->is_cancelled()) {
        // Form was cancelled - return to the course page.
        redirect(course_get_url($courseid ?: $course));
    } else if ($form->is_submitted() &&
            ($form->is_validated() || optional_param('gopreviouspage', null, PARAM_RAW))) {
        // Form was submitted (skip validation for "Previous page" button).
        $data = $form->get_submitted_data();
        //  echo "<pre>";print_r($data);echo "</pre>";exit;
        if (!isset($SESSION->feedback->is_started) OR !$SESSION->feedback->is_started == true) {
            print_error('error', '', $CFG->wwwroot.'/course/view.php?id='.$course->id);
        }
        $completedid = feedback_save_response_tmp($feedback, $courseid, $data);
        if (!empty($data->savevalues)) {
            feedback_save_response($course, $cm, $feedback, $courseid, $completedid);
            if (!$feedback->page_after_submit) {
                \core\notification::success(get_string('entries_saved', 'feedback'));
            }
            $savereturn = 'saved'; // TODO notification!
            //echo "savereturn = $savereturn<br>";
            //$savevalues = true;
        } else {
            $completion = new mod_feedback_completion($feedbackstructure);
            if (!empty($data->gonextpage)) {
                // TODO(later) smart calc next page
                $nextpage = $completion->get_next_page($gopage) ?: $gopage + 1; // TODO?
                redirect(new moodle_url($PAGE->url, array('gopage' => $nextpage)));
            } else if (!empty($data->gopreviouspage)) {
                // TODO(later) smart calc next page
                $prevpage = $completion->get_previous_page($gopage);
                redirect(new moodle_url($PAGE->url, array('gopage' => intval($prevpage))));
            }
        }
    }
}

/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($feedback->name));

if ($feedbackstructure->is_empty()) {
    \core\notification::error(get_string('no_items_available_yet', 'feedback'));
} else if ($cansubmit) {
    if (!empty($data->savevalues)) {
        // Display information after the submit.
        if ($feedback->page_after_submit) {
            echo $OUTPUT->box($feedbackstructure->page_after_submit(),
                    'generalbox boxaligncenter boxwidthwide');
        }
        if (feedback_can_view_analysis($feedback, $context, $courseid)) {
            $analysisurl = new moodle_url('/mod/feedback/analysis.php', array('id'=>$id));
            if ($courseid > 0) {
                $analysisurl->param('courseid', $courseid);
            }
            echo '<p align="center"><a href="'.$analysisurl->out().'">';
            echo get_string('completed_feedbacks', 'feedback').'</a>';
            echo '</p>';
        }

        if ($feedback->site_after_submit) {
            $url = feedback_encode_target_url($feedback->site_after_submit);
        } else {
            $url = course_get_url($courseid ? $courseid : $course->id);
        }
        echo $OUTPUT->continue_button($url);
    } else {
        // Print the items.
        $SESSION->feedback->is_started = true;
        $form->display();
    }
} else {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification(get_string('this_feedback_is_already_submitted', 'feedback'));
    echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
    echo $OUTPUT->box_end();
}
/// Finish the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

echo $OUTPUT->footer();
