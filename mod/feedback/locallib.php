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
 *
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("lib.php");

function feedback_save_response_tmp($feedback, $data) {
    global $USER;
    if (isloggedin() && !isguestuser()) {
        $completedid = feedback_save_values($USER->id, true);
    } else {
        $completedid = feedback_save_guest_values(sesskey());
    }
    return $completedid;
}

function feedback_save_response($course, $cm, $feedback, $courseid, $completedid) {
    global $USER, $DB, $SESSION;

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
    $savereturn = 'failed';
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

        }
    }
    return $savereturn;
}

function feedback_retrieve_response_tmp($feedback, $courseid) {
    global $SESSION;
    if ((!isset($SESSION->feedback->is_started)) AND
                          ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO)) {

        $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid);
        if (!$feedbackcompletedtmp) {
            $feedbackcompleted = feedback_get_current_completed($feedback->id, false, $courseid);
            if ($feedbackcompleted) {
                //copy the values to feedback_valuetmp create a completedtmp
                $feedbackcompletedtmp = feedback_set_tmp_values($feedbackcompleted);
            }
        }
    } else if (isloggedin() && !isguestuser()) {
        $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid);
    } else {
        $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid, sesskey());
    }
    return $feedbackcompletedtmp;
}

function feedback_get_page_boundaries($feedback, $gopage) {
    global $DB;
    if ($allbreaks = feedback_get_all_break_positions($feedback->id)) {
        if ($gopage <= 0) {
            $startposition = 0;
        } else {
            if (!isset($allbreaks[$gopage - 1])) {
                $gopage = count($allbreaks);
            }
            $startposition = $allbreaks[$gopage - 1];
        }
        $ispagebreak = true;
    } else {
        $startposition = 0;
        //$newpage = 0;
        $ispagebreak = false;
    }

    //get the feedbackitems after the last shown pagebreak
    $select = 'feedback = ? AND position > ?';
    $params = array($feedback->id, $startposition);
    $feedbackitems = $DB->get_records_select('feedback_item', $select, $params, 'position');

    //get the first pagebreak
    $params = array('feedback' => $feedback->id, 'typ' => 'pagebreak');
    if ($pagebreaks = $DB->get_records('feedback_item', $params, 'position')) {
        $pagebreaks = array_values($pagebreaks);
        $firstpagebreak = $pagebreaks[0];
    } else {
        $firstpagebreak = false;
    }
    return array($startposition, $firstpagebreak, $ispagebreak, $feedbackitems);
}