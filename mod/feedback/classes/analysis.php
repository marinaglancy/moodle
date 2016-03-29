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
 * Contains class mod_feedback_analysis
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Collects information and methods about feedback analysis
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_analysis extends mod_feedback_structure {

    /**
     * Constructor
     *
     * @param stdClass $feedback feedback object, in case of the template
     *     this is the current feedback the template is accessed from
     * @param cm_info $cm course module object corresponding to the $feedback
     * @param int $courseid current course (for site feedbacks only)
     */
    public function __construct($feedback, $cm, $courseid = 0) {
        parent::__construct($feedback, $cm, $courseid, 0);
        $this->courseid = $courseid; // TODO !!!!!
    }

    /**
     * If there are any new responses to the anonymous feedback, re-shuffle all
     * responses and assign response number to each of them.
     */
    function shuffle_anonym_responses() {
        global $DB;
        $params = array('feedback' => $this->feedback->id,
                        'random_response' => 0,
                        'anonymous_response' => FEEDBACK_ANONYMOUS_YES);

        if ($DB->count_records('feedback_completed', $params, 'random_response')) {
            // Get all of the anonymous records, go through them and assign a response id.
            unset($params['random_response']);
            $feedbackcompleteds = $DB->get_records('feedback_completed', $params, 'id');
            shuffle($feedbackcompleteds);
            $num = 1;
            foreach ($feedbackcompleteds as $compl) {
                $compl->random_response = $num++;
                $DB->update_record('feedback_completed', $compl);
            }
        }
    }

    /**
     * get the completeds depending on the given groupid.
     *
     * @param int $groupid
     * @return mixed array of found completeds otherwise false
     */
    function get_all_completed($groupid = 0) {
        global $DB;
        if (intval($groupid) > 0) {
            $query = "SELECT DISTINCT fbc.id, fbc.feedback, fbc.userid, fbc.timemodified,
                            fbc.random_response, fbc.anonymous_response, fbc.courseid
                        FROM {feedback_completed} fbc, {groups_members} gm
                        WHERE fbc.feedback = ?
                            AND gm.groupid = ?
                            AND fbc.userid = gm.userid";
            return $DB->get_records_sql($query,
                    array($this->feedback->id, intval($groupid)));
        } else if ($this->courseid) {
            $query = "SELECT fbc.*
                        FROM {feedback_completed} fbc
                        WHERE fbc.feedback = ?
                            AND fbc.courseid = ?
                        ORDER BY random_response";
            return $DB->get_records_sql($query, array($this->feedback->id, $this->courseid));
        } else {
            return $DB->get_records('feedback_completed', array('feedback' => $this->feedback->id));
        }
    }
}