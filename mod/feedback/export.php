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
 * prints the form to export the items as xml-file
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

require_once("../../config.php");
require_once("lib.php");

// get parameters
$id = required_param('id', PARAM_INT);

$url = new moodle_url('/mod/feedback/export.php', array('id'=>$id));
$PAGE->set_url($url);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'feedback');

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

require_capability('mod/feedback:edititems', $context);
require_sesskey();

$feedback = $PAGE->activityrecord;
$feedbackstructure = new mod_feedback_structure($feedback, $cm);

if ($feedbackstructure->is_empty()) {
    redirect(new moodle_url('/mod/feedback/edit.php', ['id' => $id, 'do_show' => 'templates']),
        get_string('no_items_available_yet', 'feedback'), 0, \core\output\notification::NOTIFY_ERROR);
}

// Large exports are likely to take their time and memory.
core_php_time_limit::raise();
raise_memory_limit(MEMORY_EXTRA);

$exportdata = $feedbackstructure->export();

$filename = 'feedback_'.$feedback->id.'.xml';

@header('Content-Type: application/xml; charset=UTF-8');
@header('Content-Disposition: attachment; filename="'.$filename.'"');
print($exportdata);
