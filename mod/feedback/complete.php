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
require_once($CFG->libdir . '/completionlib.php');

feedback_init_feedback_session();

$id = required_param('id', PARAM_INT);
$completedid = optional_param('completedid', false, PARAM_INT);
$preservevalues  = optional_param('preservevalues', 0,  PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);
$gopage = optional_param('gopage', -1, PARAM_INT);
$lastpage = optional_param('lastpage', false, PARAM_INT);
$startitempos = optional_param('startitempos', 0, PARAM_INT);
$lastitempos = optional_param('lastitempos', 0, PARAM_INT);
$anonymous_response = optional_param('anonymous_response', 0, PARAM_INT); //arb

$highlightrequired = false;

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

//if the use hit enter into a textfield so the form should not submit
if (isset($formdata->sesskey) AND
    !isset($formdata->savevalues) AND
    !isset($formdata->gonextpage) AND
    !isset($formdata->gopreviouspage)) {

    $gopage = $formdata->lastpage;
}

if (isset($formdata->savevalues)) {
    $savevalues = true;
} else {
    $savevalues = false;
}

if ($gopage < 0 AND !$savevalues) {
    if (isset($formdata->gonextpage)) {
        $gopage = $lastpage + 1;
        $gonextpage = true;
        $gopreviouspage = false;
    } else if (isset($formdata->gopreviouspage)) {
        $gopage = $lastpage - 1;
        $gonextpage = false;
        $gopreviouspage = true;
    } else {
        print_error('missingparameter');
    }
} else {
    $gonextpage = $gopreviouspage = false;
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
if ($course->id == SITEID AND !$courseid) {
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

require_course_login($course, true, $cm);
$PAGE->set_activity_record($feedback);

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

// Mark activity viewed for completion-tracking
$completion = new completion_info($course);
if (isloggedin() && !isguestuser()) {
    $completion->set_module_viewed($cm);
}

/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

if ($course->id == SITEID) {
    $PAGE->set_cm($cm, $course); // set's up global $COURSE
    $PAGE->set_pagelayout('incourse');
}

$PAGE->navbar->add(get_string('feedback:complete', 'feedback'));
$urlparams = array('id'=>$cm->id, 'gopage'=>$gopage, 'courseid'=>$course->id);
$PAGE->set_url('/mod/feedback/complete.php', $urlparams);
$PAGE->set_heading($course->fullname);
$PAGE->set_title($feedback->name);
echo $OUTPUT->header();

//check, if the feedback is open (timeopen, timeclose)
$checktime = time();
$feedback_is_closed = ($feedback->timeopen > $checktime) ||
                      ($feedback->timeclose < $checktime &&
                            $feedback->timeclose > 0);

if ($feedback_is_closed) {
    echo $OUTPUT->heading(format_string($feedback->name));
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification(get_string('feedback_is_not_open', 'feedback'));
    echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

//additional check for multiple-submit (prevent browsers back-button).
//the main-check is in view.php
$feedback_can_submit = true;
if ($feedback->multiple_submit == 0 ) {
    if (feedback_is_already_submitted($feedback->id, $courseid)) {
        $feedback_can_submit = false;
    }
}
if ($feedback_can_submit) {
    //preserving the items
    if ($preservevalues == 1) {
        if (!isset($SESSION->feedback->is_started) OR !$SESSION->feedback->is_started == true) {
            print_error('error', '', $CFG->wwwroot.'/course/view.php?id='.$course->id);
        }
        // Check if all required items have a value.
        if (feedback_check_values($startitempos, $lastitempos)) {
            $userid = $USER->id; //arb
            if (isloggedin() && !isguestuser()) {
                $completedid = feedback_save_values($USER->id, true);
            } else {
                $completedid = feedback_save_guest_values(sesskey());
            }
            if ($completedid) {
                if (!$gonextpage AND !$gopreviouspage) {
                    $preservevalues = false;// It can be stored.
                }

            } else {
                $savereturn = 'failed';
                if (isset($lastpage)) {
                    $gopage = $lastpage;
                } else {
                    print_error('missingparameter');
                }
            }
        } else {
            $savereturn = 'missing';
            $highlightrequired = true;
            if (isset($lastpage)) {
                $gopage = $lastpage;
            } else {
                print_error('missingparameter');
            }

        }
    }

    //saving the items
    if ($savevalues AND !$preservevalues) {
        //exists there any pagebreak, so there are values in the feedback_valuetmp
        $userid = $USER->id; //arb

        if ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
            $feedbackcompleted = feedback_get_current_completed($feedback->id, false, $courseid);
        } else {
            $feedbackcompleted = false;
        }
        $params = array('id' => $completedid);
        $feedbackcompletedtmp = $DB->get_record('feedback_completedtmp', $params);
        //fake saving for switchrole
        $is_switchrole = feedback_check_is_switchrole();
        if ($is_switchrole) {
            $savereturn = 'saved';
            feedback_delete_completedtmp($completedid);
        } else {
            $new_completed_id = feedback_save_tmp_values($feedbackcompletedtmp,
                                                         $feedbackcompleted,
                                                         $userid);
            if ($new_completed_id) {
                $savereturn = 'saved';
                if ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
                    feedback_send_email($cm, $feedback, $course, $userid);
                } else {
                    feedback_send_email_anonym($cm, $feedback, $course, $userid);
                }
                if (isloggedin() && !isguestuser()) {
                    // Tracking the submit.
                    $tracking = new stdClass();
                    $tracking->userid = $USER->id;
                    $tracking->feedback = $feedback->id;
                    $tracking->completed = $new_completed_id;
                    $DB->insert_record('feedback_tracking', $tracking);
                }
                unset($SESSION->feedback->is_started);

                // Update completion state
                $completion = new completion_info($course);
                if (isloggedin() && !isguestuser() && $completion->is_enabled($cm) && $feedback->completionsubmit) {
                    $completion->update_state($cm, COMPLETION_COMPLETE);
                }

            } else {
                $savereturn = 'failed';
            }
        }

    }

/** BEGIN prepare **/
/** END prepare **/
    //$maxitemcount = $DB->count_records('feedback_item', array('feedback'=>$feedback->id));

    /// Print the main part of the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    $analysisurl = new moodle_url('/mod/feedback/analysis.php', array('id'=>$id));
    if ($courseid > 0) {
        $analysisurl->param('courseid', $courseid);
    }
    echo $OUTPUT->heading(format_string($feedback->name));

    if (isset($savereturn) && $savereturn == 'saved') {
        if ($feedback->page_after_submit) {

            require_once($CFG->libdir . '/filelib.php');

            $page_after_submit_output = file_rewrite_pluginfile_urls($feedback->page_after_submit,
                                                                    'pluginfile.php',
                                                                    $context->id,
                                                                    'mod_feedback',
                                                                    'page_after_submit',
                                                                    0);

            echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
            echo format_text($page_after_submit_output,
                             $feedback->page_after_submitformat,
                             array('overflowdiv' => true));
            echo $OUTPUT->box_end();
        } else {
            echo '<p align="center">';
            echo '<b><font color="green">';
            echo get_string('entries_saved', 'feedback');
            echo '</font></b>';
            echo '</p>';
        }
        if (feedback_can_view_analysis($feedback, $context, $courseid)) {
            echo '<p align="center"><a href="'.$analysisurl->out().'">';
            echo get_string('completed_feedbacks', 'feedback').'</a>';
            echo '</p>';
        }

        if ($feedback->site_after_submit) {
            $url = feedback_encode_target_url($feedback->site_after_submit);
        } else {
            if ($courseid) {
                if ($courseid == SITEID) {
                    $url = $CFG->wwwroot;
                } else {
                    $url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
                }
            } else {
                if ($course->id == SITEID) {
                    $url = $CFG->wwwroot;
                } else {
                    $url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
                }
            }
        }
        echo $OUTPUT->continue_button($url);
    } else {
        if (isset($savereturn) && $savereturn == 'failed') {
            echo $OUTPUT->box_start('mform');
            echo '<span class="error">'.get_string('saving_failed', 'feedback').'</span>';
            echo $OUTPUT->box_end();
        }

        if (isset($savereturn) && $savereturn == 'missing') {
            echo $OUTPUT->box_start('mform');
            echo '<span class="error">'.get_string('saving_failed_because_missing_or_false_values', 'feedback').'</span>';
            echo $OUTPUT->box_end();
        }

        $completeform = new mod_feedback_completion_page($feedback, $cm, $gopage);
        $completeform->display($savereturn);
    }
} else {
    echo $OUTPUT->heading(format_string($feedback->name));
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
