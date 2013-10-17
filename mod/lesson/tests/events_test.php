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
 * Events tests.
 *
 * @package    mod_lesson
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_lesson_events_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test the grade updated event.
     *
     * There is no external API for updating a lesson grade, so the unit test will simply
     * create and trigger the event and ensure the legacy log data is returned as expected.
     */
    public function test_grade_updated() {
        // Create a grade updated event
        $event = \mod_lesson\event\grade_updated::create(array(
            'objectid' => 1,
            'userid' => 2,
            'relateduserid' => 3,
            'contextid' => 4,
            'courseid' => 5,
            'other' => array(
                'lessonname' => 'name',
                'cmid' => 7
            )
        ));

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the legacy log data is valid.
        $expected = array(5, 'lesson', 'update grade', 'essay.php?id=7', 'name', 7);
        $this->assertEventLegacyLogData($expected, $event);
    }

    /**
     * Test the essay list viewed event.
     *
     * There is no external API for viewing the list of essay submissions, so the unit test will
     * simply create and trigger the event and ensure the legacy log data is returned as expected.
     */
    public function test_essay_list_viewed() {
        // Create a essays list viewed event
        $event = \mod_lesson\event\essay_list_viewed::create(array(
            'contextid' => 1,
            'courseid' => 2,
            'other' => array(
                'cmid' => 3
            )
        ));

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the legacy log data is valid.
        $expected = array(2, 'lesson', 'view grade', 'essay.php?id=3', get_string('manualgrading', 'lesson'), 3);
        $this->assertEventLegacyLogData($expected, $event);
    }

    /**
     * Test the highscore added event.
     *
     * There is no external API for adding a highscore, so the unit test will simply create
     * and trigger the event and ensure the legacy log data is returned as expected.
     */
    public function test_highscore_added() {
        // Create a highscore added event.
        $event = \mod_lesson\event\highscore_added::create(array(
            'objectid' => 1,
            'contextid' => 2,
            'courseid' => 2,
            'other' => array(
                'cmid' => 3,
                'lessonname' => 4,
                'nickname' => 'nick'
            )
        ));

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the legacy log data is valid.
        $expected = array(2, 'lesson', 'update highscores', 'highscores.php?id=3', 'nick', 3);
        $this->assertEventLegacyLogData($expected, $event);
    }
}
