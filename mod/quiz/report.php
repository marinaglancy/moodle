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
 * This script controls the display of the quiz reports.
 *
 * @package   mod_quiz
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/quiz/report/default.php');

$cmid = optional_param('id', 0, PARAM_INT);
$q = optional_param('q', 0, PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHA);

if ($cmid) {
    list($context, $course, $cm) = $PAGE->login_to_cm('quiz', $cmid, null, PAGELOGIN_NO_AUTOLOGIN);
} else {
    list($context, $course, $cm) = $PAGE->login_to_activity('quiz', $q, null, PAGELOGIN_NO_AUTOLOGIN);
}
$quiz = $PAGE->activityrecord;

$url = new moodle_url('/mod/quiz/report.php', array('id' => $cm->id));
if ($mode !== '') {
    $url->param('mode', $mode);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');

$reportlist = quiz_report_list($context);
if (empty($reportlist)) {
    print_error('erroraccessingreport', 'quiz');
}

// Validate the requested report name.
if ($mode == '') {
    // Default to first accessible report and redirect.
    $url->param('mode', reset($reportlist));
    redirect($url);
} else if (!in_array($mode, $reportlist)) {
    print_error('erroraccessingreport', 'quiz');
}
if (!is_readable("report/$mode/report.php")) {
    print_error('reportnotfound', 'quiz', '', $mode);
}

add_to_log($course->id, 'quiz', 'report', 'report.php?id=' . $cm->id . '&mode=' . $mode,
        $quiz->id, $cm->id);

// Open the selected quiz report and display it.
$file = $CFG->dirroot . '/mod/quiz/report/' . $mode . '/report.php';
if (is_readable($file)) {
    include_once($file);
}
$reportclassname = 'quiz_' . $mode . '_report';
if (!class_exists($reportclassname)) {
    print_error('preprocesserror', 'quiz');
}

$report = new $reportclassname();
$report->display($quiz, $cm->get_course_module_record(true), $course);

// Print footer.
echo $OUTPUT->footer();
