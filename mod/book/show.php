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
 * Show/hide book chapter
 *
 * @package    mod_book
 * @copyright  2004-2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$cmid      = required_param('id', PARAM_INT);        // Course Module ID
$chapterid = required_param('chapterid', PARAM_INT); // Chapter ID

list($context, $course, $cm) = $PAGE->login_to_cm('book', $cmid, null, PAGELOGIN_NO_AUTOLOGIN);
require_sesskey();

require_capability('mod/book:edit', $context);

$book = $PAGE->activityrecord;

$PAGE->set_url('/mod/book/show.php', array('id'=>$cmid, 'chapterid'=>$chapterid));

$chapter = $DB->get_record('book_chapters', array('id'=>$chapterid, 'bookid'=>$book->id), '*', MUST_EXIST);

// Switch hidden state.
$chapter->hidden = $chapter->hidden ? 0 : 1;

// Update record.
$DB->update_record('book_chapters', $chapter);
$params = array(
    'context' => $context,
    'objectid' => $chapter->id
);
$event = \mod_book\event\chapter_updated::create($params);
$event->add_record_snapshot('book_chapters', $chapter);
$event->trigger();

// Change visibility of subchapters too.
if (!$chapter->subchapter) {
    $chapters = $DB->get_records('book_chapters', array('bookid'=>$book->id), 'pagenum', 'id, subchapter, hidden');
    $found = 0;
    foreach ($chapters as $ch) {
        if ($ch->id == $chapter->id) {
            $found = 1;
        } else if ($found and $ch->subchapter) {
            $ch->hidden = $chapter->hidden;
            $DB->update_record('book_chapters', $ch);

            $params = array(
                'context' => $context,
                'objectid' => $ch->id
            );
            $event = \mod_book\event\chapter_updated::create($params);
            $event->trigger();

        } else if ($found) {
            break;
        }
    }
}

// MDL-39963 Decide what to do with those logs.
add_to_log($course->id, 'course', 'update mod', '../mod/book/view.php?id='.$cmid, 'book '.$book->id);
add_to_log($course->id, 'book', 'update', 'view.php?id='.$cmid, $book->id, $cmid);

book_preload_chapters($book); // fix structure
$DB->set_field('book', 'revision', $book->revision+1, array('id'=>$book->id));

redirect('view.php?id='.$cmid.'&chapterid='.$chapter->id);

