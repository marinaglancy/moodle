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
 * Redirects the user to either a lesson or to the lesson statistics
 *
 * @package   mod_lesson
 * @category  grade
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/**
 * Require config.php
 */
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/lesson/locallib.php');

$cmid = required_param('id', PARAM_INT);
list($context, $course, $cm) = $PAGE->login_to_cm('lesson', $cmid, null, PAGELOGIN_NO_AUTOLOGIN);

$PAGE->set_url('/mod/lesson/grade.php', array('id'=>$cmid));

if (has_capability('mod/lesson:edit', $context)) {
    redirect('report.php?id='.$cmid);
} else {
    redirect('view.php?id='.$cmid);
}
