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
