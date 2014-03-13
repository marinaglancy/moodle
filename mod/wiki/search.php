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
 * @package mod_wiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/wiki/lib.php');
require_once($CFG->dirroot . '/mod/wiki/locallib.php');
require_once($CFG->dirroot . '/mod/wiki/pagelib.php');

$search = optional_param('searchstring', null, PARAM_ALPHANUMEXT);
$courseid = required_param('courseid', PARAM_INT);
$searchcontent = optional_param('searchwikicontent', 0, PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

list($context, $course, $cm) = $PAGE->login_to_cm('wiki', $cmid, $courseid);
$wiki = $PAGE->activityrecord;

// @TODO: Fix call to wiki_get_subwiki_by_group
if (!$gid = groups_get_activity_group($cm)) {
    $gid = 0;
}
if (!$subwiki = wiki_get_subwiki_by_group($cm->instance, $gid)) {
    print_error('incorrectsubwikiid', 'wiki');
}

if (!wiki_user_can_view($subwiki, $wiki)) {
    print_error('cannotviewfiles', 'wiki');
}

$wikipage = new page_wiki_search($wiki, $subwiki, $cm);

$wikipage->set_search_string($search, $searchcontent);

$wikipage->set_title(get_string('search'));

$wikipage->print_header();

$wikipage->print_content();

$wikipage->print_footer();
