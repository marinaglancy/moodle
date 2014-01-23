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
 * Class to represent an item in mod_feedback recent activity.
 *
 * @package    mod_feedback
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_feedback;
defined('MOODLE_INTERNAL') || die();

/**
 * Class to represent an item in mod_feedback recent activity.
 *
 * @package    mod_feedback
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recentactivity extends \core_course\recentactivity {
    /**
     * @var \cm_info $cm
     */
    var $cm;

    protected static function create($cm, $timemodified, $content, $user) {
        $a = new static();
        $a->type = 'feedback';
        $a->course = $cm->get_course();
        $a->cm = $cm;
        $a->timestamp = $timemodified;
        $a->content = $content;
        $a->user = $user;
        return $a;
    }

    public static function get_recentactivity_types($forblock = false) {
        if ($forblock) {
            return array();
        } else {
            return array(
                'feedback' => 'New feedbacks' // TODO localize
            );
        }
    }

    public static function get_recentactivity($course, $timemodified, $filters = array(), $forblock = false) {
        global $USER, $DB;

        if ($forblock) {
            // No recent activity for the block.
            return array();
        }

        $cm = $filters['cm']; // TODO
        $groupid = isset($filters['groupid']) ? $filters['groupid'] : "";
        $userid = isset($filters['userid']) ? $filters['userid'] : "";

        $modinfo = $cm->get_modinfo();
        $activities = array();

        if (!$cm->uservisible) {
            return array();
        }


        $sqlargs = array();

        $sql = " SELECT fk.id , fc.timemodified , ".\user_picture::fields('u', null, 'userid')."
                                                FROM {feedback_completed} fc
                                                    JOIN {feedback} fk ON fk.id = fc.feedback
                                                    JOIN {user} u ON u.id = fc.userid ";

        if ($groupid) {
            $sql .= " JOIN {groups_members} gm ON  gm.userid=u.id ";
        }

        $sql .= " WHERE fc.timemodified > ? AND fk.id = ? ";
        $sqlargs[] = $timemodified;
        $sqlargs[] = $cm->instance;

        if ($userid) {
            $sql .= " AND u.id = ? ";
            $sqlargs[] = $userid;
        }

        if ($groupid) {
            $sql .= " AND gm.groupid = ? ";
            $sqlargs[] = $groupid;
        }

        if (!$feedbackitems = $DB->get_records_sql($sql, $sqlargs)) {
            return array();
        }

        $checkgroups = ($cm->effectivegroupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $cm->context));

        foreach ($feedbackitems as $feedbackitem) {
            if ($feedbackitem->userid != $USER->id) {

                if ($checkgroups) {
                    $usersgroups = groups_get_all_groups($cm->course,
                                                         $feedbackitem->userid,
                                                         $cm->groupingid);
                    if (empty($usersgroups) ||
                            !count(array_intersect(array_keys($usersgroups), $modinfo->groups[$cm->groupingid]))) {
                        continue;
                    }
                }
            }

            $activities[] = self::create($cm,
                $feedbackitem->timemodified,
                (object)array(
                    'feedbackid' => $feedbackitem->id,
                    'feedbackuserid' => $feedbackitem->userid
                ),
                \user_picture::unalias($feedbackitem, null, 'userid')
            );
        }

        return $activities;
    }

    public function display($detail = true) {
        global $CFG, $OUTPUT;

        echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

        echo "<tr><td class=\"userpicture\" valign=\"top\">";
        echo $OUTPUT->user_picture($this->user, array('courseid'=>$this->cm->course));
        echo "</td><td>";

        if ($detail) {
            echo '<div class="title">';
            echo "<img src=\"" . $OUTPUT->pix_url('icon', $this->cm->modname) . "\" ".
                 "class=\"icon\" alt=\"{$this->cm->modfullname}\" />";
            echo \html_writer::link($this->cm->get_url(), $this->cm->get_formatted_name());
            echo '</div>';
        }

        echo '<div class="title">';
        echo '</div>';

        echo '<div class="user">';
        $coursecontext = \context_course::instance($this->cm->course);
        $fullname = fullname($this->user, has_capability('moodle/site:viewfullnames', $coursecontext));
        echo "<a href=\"$CFG->wwwroot/user/view.php?id={$this->user->id}&amp;course={$this->cm->course}\">"
             ."{$fullname}</a> - ".userdate($this->timestamp);
        echo '</div>';

        echo "</td></tr></table>";

        return;
    }

    public function display_in_block() {
        return '';
    }
}
