<?php

/// Displays external information about a course

    require_once("../config.php");
    require_once("lib.php");

    $id   = optional_param('id', false, PARAM_INT); // Course id
    $name = optional_param('name', false, PARAM_RAW); // Course short name

    $PAGE->login(0, PAGELOGIN_IF_REQUIRED_ONLY);

    if (!$id and !$name) {
        print_error("unspecifycourseid");
    }

    if ($name) {
        $errorcode = "invalidshortname";
        $params = array("shortname"=>$name);
    } else {
        $errorcode = "invalidcourseid";
        $params = array("id"=>$id);
    }
    if (!$course = $DB->get_record("course", $params)) {
        print_error($errorcode);
    }

    $context = context_course::instance($course->id);
    if (!$course->visible and !has_capability('moodle/course:viewhiddencourses', $context)) {
        print_error($errorcode);
    }

    $PAGE->set_course($course);
    $PAGE->set_pagelayout('course');
    $PAGE->set_url('/course/info.php', array('id' => $course->id));
    $PAGE->set_title(get_string("summaryof", "", $course->fullname));
    $PAGE->set_heading(get_string('courseinfo'));
    $PAGE->navbar->add(get_string('summary'));

    echo $OUTPUT->header();

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


