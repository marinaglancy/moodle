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
 * Help functions for mod_feedback
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/feedback/lib.php');

/**
 * Saves unfinished response to the temporary table
 *
 * This is called when user proceeds to the next/previous page in the complete form
 * and also right after the form submit.
 * After the form submit the {@link feedback_save_response()} is called to
 * move response from temporary table to completion table.
 *
 * @param stdClass $feedback record from db table 'feedback'
 * @param stdClass $data data from the form mod_feedback_complete_form
 * @return type
 */
function feedback_save_response_tmp($feedback, $courseid, $data) {
    global $DB;
    if (!$completedtmp = feedback_get_current_completed_tmp($feedback, $courseid)) {
        $completedtmp = feedback_create_current_completed_tmp($feedback, $courseid);
    } else {
        $DB->update_record('feedback_completedtmp',
                ['id' => $completedtmp->id, 'timemodified' => time()]);
    }

    // Find all existing values.
    $existingvalues = $DB->get_records_menu('feedback_valuetmp',
            ['completed' => $completedtmp->id], '', 'item, id');

    // Loop through all feedback items and save the ones that are present in $data.
    $allitems = $DB->get_records('feedback_item',
            ['feedback' => $completedtmp->feedback, 'hasvalue' => 1]);
    foreach ($allitems as $item) {
        $keyname = $item->typ . '_' . $item->id;
        if (!isset($data->$keyname)) {
            // This item is either on another page or dependency was not met - nothing to save.
            continue;
        }

        $newvalue = ['item' => $item->id, 'completed' => $completedtmp->id, 'course_id' => $completedtmp->courseid];

        // Convert the value to string that can be stored in 'feedback_valuetmp' or 'feedback_value'.
        $itemobj = feedback_get_item_class($item->typ);
        $newvalue['value'] = $itemobj->create_value($data->$keyname);

        // Update or insert the value in the 'feedback_valuetmp' table.
        if (array_key_exists($item->id, $existingvalues)) {
            $newvalue['id'] = $existingvalues[$item->id];
            $DB->update_record('feedback_valuetmp', $newvalue);
        } else {
            $DB->insert_record('feedback_valuetmp', $newvalue);
        }
    }

    return $completedtmp->id;
}

/**
 * Saves the response
 *
 * The form data has already been stored in the temporary table in
 * {@link feedback_save_response_tmp()}. This function copies the values
 * from the temporary table to the completion table.
 * It is also responsible for sending email notifications when applicable.
 *
 * @param stdClass $course
 * @param cm_info $cm
 * @param stdClass $feedback record from db table 'feedback'
 * @param int $courseid
 * @param int $completedtmpid
 */
function feedback_save_response($course, $cm, $feedback, $courseid, $completedtmpid) {
    global $USER, $DB, $SESSION;

    $feedbackcompleted = feedback_get_last_completed($feedback, $courseid);
    $params = array('id' => $completedtmpid);
    $feedbackcompletedtmp = $DB->get_record('feedback_completedtmp', $params);

    if (feedback_check_is_switchrole()) {
        // We do not actually save anything if the role is switched, just delete temporary values.
        feedback_delete_completedtmp($completedtmpid);
        return;
    }

    // Save values.
    $completedid = feedback_save_tmp_values($feedbackcompletedtmp, $feedbackcompleted);

    // Send email.
    if ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
        feedback_send_email($cm, $feedback, $course, $USER);
    } else {
        feedback_send_email_anonym($cm, $feedback, $course);
    }

    unset($SESSION->feedback->is_started);

    // Update completion state
    $completion = new completion_info($course);
    if (isloggedin() && !isguestuser() && $completion->is_enabled($cm) && $feedback->completionsubmit) {
        $completion->update_state($cm, COMPLETION_COMPLETE);
    }
}

/**
 *
 * @param stdClass $feedback
 * @param int $gopage
 * @return array [$startposition, $firstpagebreak, $ispagebreak, $feedbackitems]
 */
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

/**
 * Returns the temporary completion record for the current user or guest session
 *
 * @param stdClass $feedback record from db table 'feedback'
 * @param int $courseid current course (only for site feedbacks)
 * @return stdClass record from feedback_completedtmp or false if not found
 */
function feedback_get_current_completed_tmp($feedback, $courseid = false) {
    global $USER, $DB;
    $params = array('feedback' => $feedback->id);
    if ($feedback->course == SITEID) {
        $params['courseid'] = $courseid ? intval($courseid): SITEID;
    }
    if (isloggedin() && !isguestuser()) {
        $params['userid'] = $USER->id;
    } else {
        $params['guestid'] = sesskey();
    }
    return $DB->get_record('feedback_completedtmp', $params);
}

/**
 * Creates a new record in the 'feedback_completedtmp' table for the current user/guest session
 *
 * @param stdClass $feedback record from db table 'feedback'
 * @param int $courseid current course (only for site feedbacks)
 * @return stdClass record from feedback_completedtmp or false if not found
 */
function feedback_create_current_completed_tmp($feedback, $courseid = false) {
    global $USER, $DB;
    $record = (object)['feedback' => $feedback->id];
    if ($feedback->course == SITEID) {
        $record->courseid = $courseid ? intval($courseid): SITEID;
    }
    if (isloggedin() && !isguestuser()) {
        $record->userid = $USER->id;
    } else {
        $record->guestid = sesskey();
    }
    $record->timemodified = time();
    $record->anonymous_response = $feedback->anonymous;
    $id = $DB->insert_record('feedback_completedtmp', $record);
    return $DB->get_record('feedback_completedtmp', ['id' => $id]);
}

/**
 * Retrieves the last completion record for the current user
 *
 * @param stdClass $feedback
 * @param int $courseid current course (only for site feedbacks)
 * @return stdClass record from feedback_completed or false if not found
 */
function feedback_get_last_completed($feedback, $courseid = false) {
    global $USER, $DB;
    if (isloggedin() || isguestuser()) {
        // Not possible to retrieve completed feedback for guests.
        return false;
    }
    if ($feedback->anonymous == FEEDBACK_ANONYMOUS_YES) {
        // Not possible to retrieve completed anonymous feedback.
        return false;
    }
    $params = array('feedback' => $feedback->id, 'userid' => $USER->id);
    if ($feedback->course == SITEID) {
        $params['courseid'] = $courseid ? intval($courseid): SITEID;
    }
    return $DB->get_record('feedback_completed', $params);
}
