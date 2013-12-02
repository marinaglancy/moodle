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
 * Unit tests for workshop api class defined in mod/workshop/locallib.php
 *
 * @package    mod_workshop
 * @category   phpunit
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/workshop/lib.php'); // Include the code to test
require_once($CFG->dirroot . '/mod/workshop/locallib.php'); // Include the code to test
require_once($CFG->dirroot . '/lib/cronlib.php'); // Include the code to test


/**
 * Test cases for the internal workshop api
 */
class mod_workshop_lib_testcase extends advanced_testcase {


    function test_switch_phase_updated_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $workshop = $this->getDataGenerator()->create_module('workshop', array('course' => $course));
        $context = context_course::instance($course->id);
        $workshop->phase = 20;
        $workshop->phaseswitchassessment = 1;
        $workshop->submissionend = time() - 1;

        $event = \mod_workshop\event\switch_phase_updated::create(array(
            'objectid' => $workshop->id,
            'context' => $context,
            'courseid' => $course->id,
            'other' => array('workshopphase' => $workshop->phase)
        ));

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        print_object($event);

        // Check that the legacy log data is valid.
        // $expected = array($this->eventcourse->id, 'scorm', 'pre-view', 'view.php?id=' . $this->eventcm->id,
                // $this->eventscorm->id, $this->eventcm->id);
        // $this->assertEventLegacyLogData($expected, $event);

        // print_object($workshop);

    }

    // function test_aggregate_grade_created() {
    //     $this->resetAfterTest();
    //     $this->setAdminUser();

    //     $course = $this->getDataGenerator()->create_course();
    //     $workshop = $this->getDataGenerator()->create_module('workshop', array('course' => $course));

    //     $assessments = array();

    // }


}
