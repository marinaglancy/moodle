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
 * Provide interface for topics AJAX course formats
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package course
 */

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}
require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot.'/course/lib.php');

$courseid   = required_param('course', PARAM_INT);
$changecompl = required_param('changecompl', PARAM_ALPHANUMEXT);

require_login($courseid);
require_sesskey();
require_capability('report/progress:view', $PAGE->context);
require_capability('moodle/course:overridecompletion', $PAGE->context);

// Get group mode
$group = groups_get_course_group($PAGE->course, true); // Supposed to verify group
if ($group === 0 && $PAGE->course->groupmode == SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups', $PAGE->context);
}

$completion = new completion_info($PAGE->course);
$activities = $completion->get_activities();

list($userid, $cmid, $newstate) = preg_split('/-/', $changecompl, 3);
// Make sure the activity and user are tracked.
if (isset($activities[$cmid]) &&
    $completion->get_num_tracked_users('u.id = :userid', array('userid' => (int)$userid), $group)) {
    $completion->update_state($activities[$cmid], $newstate, $userid);
}
