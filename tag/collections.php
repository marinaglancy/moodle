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
 * @package    core
 * @subpackage tag
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');

$action = optional_param('action', null, PARAM_ALPHA);
$tcid = optional_param('tc', null, PARAM_INT);
$tagareaid = optional_param('ta', null, PARAM_INT);

require_login();

if (empty($CFG->usetags)) {
    print_error('tagsaredisabled', 'tag');
}

$pageurl = new moodle_url('/tag/collections.php');
admin_externalpage_setup('managetags', '', null, $pageurl, array('pagelayout' => 'standard'));

$tagcoll = core_tag_collection::get_by_id($tcid);
$tagarea = core_tag_area::get_by_id($tagareaid);

if ($action === 'colladd' || ($action === 'colledit' && $tagcoll && empty($tagcoll->component))) {
    $form = new core_tag_collection_form($pageurl, $tagcoll);
    if ($form->is_cancelled()) {
        redirect($pageurl);
    } else if ($data = $form->get_data()) {
        if ($action === 'colladd') {
            core_tag_collection::create($data);
        } else {
            core_tag_collection::update($tagcoll, $data);
        }
        redirect($pageurl);
    } else {
        $title = ($action === 'colladd') ?
                get_string('addtagcoll', 'tag') :
                get_string('edittagcoll', 'tag', core_tag_collection::display_name($tagcoll));
        $PAGE->navbar->add($title);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($title, 2);
        $form->display();
        echo $OUTPUT->footer();
        exit;
    }
} else if ($action === 'colldelete' && $tagcoll && !$tagcoll->component) {
    // TODO confirmation.
    require_sesskey();
    core_tag_collection::delete($tagcoll);
    redirect($pageurl);
} else if ($action === 'collmoveup' && $tagcoll) {
    require_sesskey();
    core_tag_collection::change_sortorder($tagcoll, -1);
    redirect($pageurl);
} else if ($action === 'collmovedown' && $tagcoll) {
    require_sesskey();
    core_tag_collection::change_sortorder($tagcoll, 1);
    redirect($pageurl);
} else if (($action === 'areaenable' || $action === 'areadisable') && $tagarea) {
    require_sesskey();
    $data = array('enabled' => ($action === 'areaenable') ? 1 : 0);
    core_tag_area::update($tagarea, $data);
    redirect($pageurl);
} else if ($action === 'areasetcoll' && $tagarea) {
    require_sesskey();
    if ($newtagcollid = optional_param('areacollid', null, PARAM_INT)) {
        core_tag_area::update($tagarea, array('tagcollid' => $newtagcollid));
        redirect($pageurl);
    }
}

$tagareastable = new core_tag_areas_table();
$colltable = new core_tag_collections_table($pageurl);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('tagcollections', 'core_tag'), 3);
echo html_writer::table($colltable);
$url = new moodle_url($pageurl, array('action' => 'colladd'));
echo html_writer::div(html_writer::link($url, get_string('addtagcoll', 'tag')), 'addtagcoll', array('style' => 'text-align:right')); // TODO proper styles

echo $OUTPUT->heading(get_string('tagareas', 'core_tag'), 3);
echo html_writer::table($tagareastable);

echo $OUTPUT->footer();
