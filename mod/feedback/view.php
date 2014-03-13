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
require_once("../../config.php");
require_once("lib.php");

$cmid = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);

$current_tab = 'view';

list($context, $course, $cm) = $PAGE->login_to_cm('feedback', $cmid, $courseid, PAGELOGIN_ALLOW_FRONTPAGE_GUEST);
$courseid = $course->id;
$feedback = $PAGE->activityrecord;
if ($feedback->anonymous != FEEDBACK_ANONYMOUS_YES) {
    // Guests can not answer non-anonymous feedback, request login.
    $PAGE->login_to_cm('feedback', $cm, $course, 0);
}

$feedback_complete_cap = false;

if (has_capability('mod/feedback:complete', $context)) {
    $feedback_complete_cap = true;
}

if (isset($CFG->feedback_allowfullanonymous)
            AND $CFG->feedback_allowfullanonymous
            AND $course->id == SITEID
            AND $feedback->anonymous == FEEDBACK_ANONYMOUS_YES ) {
    $feedback_complete_cap = true;
}

//check whether the feedback is mapped to the given courseid
if ($course->id == SITEID AND !has_capability('mod/feedback:edititems', $context)) {
    if ($DB->get_records('feedback_sitecourse_map', array('feedbackid'=>$feedback->id))) {
        $params = array('feedbackid'=>$feedback->id, 'courseid'=>$courseid);
        if (!$DB->get_record('feedback_sitecourse_map', $params)) {
            print_error('invalidcoursemodule');
        }
    }
}

// Trigger module viewed event.
$event = \mod_feedback\event\course_module_viewed::create(array(
    'objectid' => $feedback->id,
    'context' => $context,
    'other' => array(
        'anonymous' => $feedback->anonymous
    )
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('feedback', $feedback);
$event->trigger();

/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

$PAGE->set_url('/mod/feedback/view.php', array('id'=>$cm->id, 'do_show'=>'view'));
$PAGE->set_title($feedback->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

$previewimg = $OUTPUT->pix_icon('t/preview', get_string('preview'));
$previewlnk = new moodle_url('/mod/feedback/print.php', array('id' => $cmid));
$preview = html_writer::link($previewlnk, $previewimg);

echo $OUTPUT->heading(format_string($feedback->name) . $preview);

// Print the tabs.
require('tabs.php');

//show some infos to the feedback
if (has_capability('mod/feedback:edititems', $context)) {
    //get the groupid
    $groupselect = groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/feedback/view.php?id='.$cm->id, true);
    $mygroupid = groups_get_activity_group($cm);

    echo $OUTPUT->box_start('boxaligncenter boxwidthwide');
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
    echo $OUTPUT->heading(get_string('description', 'feedback'), 3);
}
echo $OUTPUT->box_start('generalbox boxwidthwide');
$options = (object)array('noclean'=>true);
echo format_module_intro('feedback', $feedback, $cm->id);
echo $OUTPUT->box_end();

if (has_capability('mod/feedback:edititems', $context)) {
    require_once($CFG->libdir . '/filelib.php');

    $page_after_submit_output = file_rewrite_pluginfile_urls($feedback->page_after_submit,
                                                            'pluginfile.php',
                                                            $context->id,
                                                            'mod_feedback',
                                                            'page_after_submit',
                                                            0);

    echo $OUTPUT->heading(get_string("page_after_submit", "feedback"), 3);
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    echo format_text($page_after_submit_output,
                     $feedback->page_after_submitformat,
                     array('overflowdiv'=>true));

    echo $OUTPUT->box_end();
}

if ( (intval($feedback->publish_stats) == 1) AND
                ( has_capability('mod/feedback:viewanalysepage', $context)) AND
                !( has_capability('mod/feedback:viewreports', $context)) ) {

    $params = array('userid'=>$USER->id, 'feedback'=>$feedback->id);
    if ($multiple_count = $DB->count_records('feedback_tracking', $params)) {
        $url_params = array('id'=>$cmid, 'courseid'=>$courseid);
        $analysisurl = new moodle_url('/mod/feedback/analysis.php', $url_params);
        echo '<div class="mdl-align"><a href="'.$analysisurl->out().'">';
        echo get_string('completed_feedbacks', 'feedback').'</a>';
        echo '</div>';
    }
}

//####### mapcourse-start
if (has_capability('mod/feedback:mapcourse', $context)) {
    if ($feedback->course == SITEID) {
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
        echo '<div class="mdl-align">';
        echo '<form action="mapcourse.php" method="get">';
        echo '<fieldset>';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<input type="hidden" name="id" value="'.$cmid.'" />';
        echo '<button type="submit">'.get_string('mapcourses', 'feedback').'</button>';
        echo $OUTPUT->help_icon('mapcourse', 'feedback');
        echo '</fieldset>';
        echo '</form>';
        echo '<br />';
        echo '</div>';
        echo $OUTPUT->box_end();
    }
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

    //check multiple Submit
    $feedback_can_submit = true;
    if ($feedback->multiple_submit == 0 ) {
        if (feedback_is_already_submitted($feedback->id, $courseid)) {
            $feedback_can_submit = false;
        }
    }
    if ($feedback_can_submit) {
        //if the user is not known so we cannot save the values temporarly
        if (!isloggedin() or isguestuser()) {
            $completefile = 'complete_guest.php';
            $guestid = sesskey();
        } else {
            $completefile = 'complete.php';
            $guestid = false;
        }
        $url_params = array('id'=>$cmid, 'courseid'=>$courseid, 'gopage'=>0);
        $completeurl = new moodle_url('/mod/feedback/'.$completefile, $url_params);

        $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid, $guestid);
        if ($feedbackcompletedtmp) {
            if ($startpage = feedback_get_page_to_continue($feedback->id, $courseid, $guestid)) {
                $completeurl->param('gopage', $startpage);
            }
            echo '<a href="'.$completeurl->out().'">'.get_string('continue_the_form', 'feedback').'</a>';
        } else {
            echo '<a href="'.$completeurl->out().'">'.get_string('complete_the_form', 'feedback').'</a>';
        }
    } else {
        echo $OUTPUT->notification(get_string('this_feedback_is_already_submitted', 'feedback'));
        echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$courseid);
    }
    echo $OUTPUT->box_end();
}
//####### completed-end

/// Finish the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

echo $OUTPUT->footer();

