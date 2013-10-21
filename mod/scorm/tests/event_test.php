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
 * This file contains tests for scorm events.
 *
 * @package    mod_scorm
 * @copyright  2013 onwards Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/mod/scorm/locallib.php');
require_once($CFG->dirroot . '/mod/scorm/lib.php');

/**
 * Test class for various events related to Scorm.
 *
 * @package    mod_scorm
 * @copyright  2013 onwards Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scorm_event_testcase extends advanced_testcase {

    protected $eventcourse;

    protected $eventuser;

    protected $eventscorm;

    protected $eventcm;

    protected function setUp() {
        $this->setAdminUser();
        $this->eventcourse = $this->getDataGenerator()->create_course();
        $this->eventuser = $this->getDataGenerator()->create_user();
        $record = new stdClass();
        $record->course = $this->eventcourse->id;
        $this->eventscorm = $this->getDataGenerator()->create_module('scorm', $record);
        $this->eventcm = get_coursemodule_from_instance('scorm', $this->eventscorm->id);
    }

    public function test_attempt_deleted_event() {

        global $USER;

        $this->resetAfterTest();
        scorm_insert_track(2, $this->eventscorm->id, 1, 4, 'cmi.core.score.raw', 10);
        $sink = $this->redirectEvents();
        scorm_delete_attempt(2, $this->eventscorm, 4);
        $events = $sink->get_events();
        $sink->close();
        $event = reset($events);

        // Verify data.
        $this->assertCount(1, $events);
        $this->assertInstanceOf('\mod_scorm\event\attempt_deleted', $event);
        $this->assertEquals($USER->id, $event->userid);
        $this->assertEquals(context_module::instance($this->eventscorm->id), $event->get_context());
        $this->assertEquals(4, $event->other['attemptid']);
        $this->assertEquals(2, $event->relateduserid);
        $expected = array($this->eventcourse->id, 'scorm', 'delete attempts', 'report.php?id=' . $this->eventcm->id,
                4, $this->eventscorm->id);
        $this->assertEventLegacyLogData($expected, $events[0]);

        // Test event validations.
        $this->setExpectedException('coding_exception');
        \mod_scorm\event\attempt_deleted::create(array(
            'contextid' => 5,
            'relateduserid' => 2
        ));
        $this->fail('event \\mod_scorm\\event\\attempt_deleted is not validating events properly');
    }
}

