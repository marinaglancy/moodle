<?php
      // Display all the interfaces for importing data into a specific course

    require_once('../config.php');

    $id = required_param('id', PARAM_INT);   // course id to import TO
    list($context, $course) = $PAGE->login($id);
    require_capability('moodle/site:viewreports', $context); // basic capability for listing of reports

    $PAGE->set_pagelayout('standard');

    $strreports = get_string('reports');

    $PAGE->set_url(new moodle_url('/course/report.php', array('id'=>$id)));
    $PAGE->set_title($course->fullname.': '.$strreports);
    $PAGE->set_heading($course->fullname.': '.$strreports);
    echo $OUTPUT->header();

    $reports = core_component::get_plugin_list('coursereport');

    foreach ($reports as $report => $reportdirectory) {
        $pluginfile = $reportdirectory.'/mod.php';
        if (file_exists($pluginfile)) {
            ob_start();
            include($pluginfile);  // Fragment for listing
            $html = ob_get_contents();
            ob_end_clean();
            // add div only if plugin accessible
            if ($html !== '') {
                echo '<div class="plugin">';
                echo $html;
                echo '</div>';
            }
        }
    }

    echo $OUTPUT->footer();

