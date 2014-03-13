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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Wiki files management
 *
 * @package mod_wiki
 * @copyright 2011 Dongsheng Cai <dongsheng@moodle.com>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/wiki/lib.php');
require_once($CFG->dirroot . '/mod/wiki/locallib.php');

$pageid       = required_param('pageid', PARAM_INT); // Page ID
$wid          = optional_param('wid', 0, PARAM_INT); // Wiki ID
$currentgroup = optional_param('group', 0, PARAM_INT); // Group ID
$userid       = optional_param('uid', 0, PARAM_INT); // User ID
$groupanduser = optional_param('groupanduser', null, PARAM_TEXT);

$PAGE->login_expected();
if (!$page = wiki_get_page($pageid)) {
    print_error('incorrectpageid', 'wiki');
}

if ($groupanduser) {
    list($currentgroup, $userid) = explode('-', $groupanduser);
    $currentgroup = clean_param($currentgroup, PARAM_INT);
    $userid       = clean_param($userid, PARAM_INT);
}

if ($wid) {
    // in group mode
    list($context, $course, $cm) = $PAGE->login_to_activity('wiki', $wid);
    if (!$subwiki = wiki_get_subwiki_by_group($wid, $currentgroup, $userid)) {
        // create subwiki if doesn't exist
        $subwikiid = wiki_add_subwiki($wid, $currentgroup, $userid);
        $subwiki = wiki_get_subwiki($subwikiid);
    }
} else {
    // no group
    if (!$subwiki = wiki_get_subwiki($page->subwikiid)) {
        print_error('incorrectsubwikiid', 'wiki');
    }
    list($context, $course, $cm) = $PAGE->login_to_activity('wiki', $subwiki->wikiid);
}
$wiki = $PAGE->activityrecord;

$PAGE->set_url('/mod/wiki/files.php', array('pageid'=>$pageid));

if (!wiki_user_can_view($subwiki, $wiki)) {
    print_error('cannotviewfiles', 'wiki');
}

$PAGE->set_title(get_string('wikifiles', 'wiki'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(format_string(get_string('wikifiles', 'wiki')));
echo $OUTPUT->header();
echo $OUTPUT->heading($wiki->name);
echo $OUTPUT->box(format_module_intro('wiki', $wiki, $PAGE->cm->id), 'generalbox', 'intro');

$renderer = $PAGE->get_renderer('mod_wiki');

$tabitems = array('view' => 'view', 'edit' => 'edit', 'comments' => 'comments', 'history' => 'history', 'map' => 'map', 'files' => 'files', 'admin' => 'admin');

$options = array('activetab'=>'files');
echo $renderer->tabs($page, $tabitems, $options);


echo $OUTPUT->box_start('generalbox');
echo $renderer->wiki_print_subwiki_selector($PAGE->activityrecord, $subwiki, $page, 'files');
echo $renderer->wiki_files_tree($context, $subwiki);
echo $OUTPUT->box_end();

if (has_capability('mod/wiki:managefiles', $context)) {
    echo $OUTPUT->single_button(new moodle_url('/mod/wiki/filesedit.php', array('subwiki'=>$subwiki->id, 'pageid'=>$pageid)), get_string('editfiles', 'wiki'), 'get');
}
echo $OUTPUT->footer();
