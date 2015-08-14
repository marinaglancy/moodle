<?php


require_once('../config.php');

require_login();

$PAGE->set_url(new moodle_url('/tag/test.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->requires->js_call_amd('core/tag', 'init', array());
echo $OUTPUT->header();
echo $OUTPUT->heading('Testing weird template bug');

$a = (object)array('id' => 1, 'flag' => 0, 'changeurl' => $PAGE->url);
echo $OUTPUT->render_from_template('core_tag/tagflag', $a);
echo $OUTPUT->footer();