<?php

/// Displays external information about a course

require_once("../config.php");
require_once("lib.php");

$id = optional_param('id', false, PARAM_INT); // Course id
$name = optional_param('name', false, PARAM_RAW); // Course short name
$catid = optional_param('catid', false, PARAM_INT); // Course category id

if ($CFG->forcelogin) {
    require_login();
}

if ($catid) {
    require_once($CFG->libdir . '/coursecatlib.php');
    $category = coursecat::get($catid);
    $context = context_category::instance($category->id);
    $PAGE->set_context($context);
    $PAGE->set_url('/course/info.php', array('catid' => $category->id));
    $PAGE->set_category_by_id($catid);
    $PAGE->set_title(get_string("summaryof", "", $category->get_formatted_name()));
    $PAGE->set_heading(get_string('categoryinfo'));
} else {
    if (!$id and !$name) {
        print_error("unspecifycourseid");
    }
    if ($name) {
        if (!$course = $DB->get_record("course", array("shortname" => $name))) {
            print_error("invalidshortname");
        }
    } else {
        if (!$course = $DB->get_record("course", array("id" => $id))) {
            print_error("invalidcourseid");
        }
    }
    $context = context_course::instance($course->id);
    $PAGE->set_context($context);
    if (!$course->visible and !has_capability('moodle/course:viewhiddencourses', $context)) {
        print_error('coursehidden', '', $CFG->wwwroot . '/');
    }
    $PAGE->set_url('/course/info.php', array('id' => $course->id));
    $PAGE->set_title(get_string("summaryof", "", $course->fullname));
    $PAGE->set_heading(get_string('courseinfo'));
    $PAGE->set_course($course);
}

$site = get_site();

$PAGE->set_pagelayout('popup');

echo $OUTPUT->header();
echo $OUTPUT->heading('<a href="view.php?id=' . $course->id . '">' . format_string($course->fullname) . '</a><br />(' . format_string($course->shortname, true, array('context' => $context)) . ')');

// print enrol info
if ($texts = enrol_get_course_description_texts($course)) {
    echo $OUTPUT->box_start('generalbox icons');
    echo implode($texts);
    echo $OUTPUT->box_end();
}

$courserenderer = $PAGE->get_renderer('core', 'course');
echo $courserenderer->course_info_box($course);

echo "<br />";

echo $OUTPUT->footer();


