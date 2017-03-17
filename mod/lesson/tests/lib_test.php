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
 * Unit tests for mod/lesson/lib.php.
 *
 * @package    mod_lesson
 * @category   test
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lesson/lib.php');

/**
 * Unit tests for mod/lesson/lib.php.
 *
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class mod_lesson_lib_testcase extends advanced_testcase {
    /**
     * Test for lesson_get_group_override_priorities().
     */
    public function test_lesson_get_group_override_priorities() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $lessonmodule = $this->getDataGenerator()->create_module('lesson', array('course' => $course->id));

        $this->assertNull(lesson_get_group_override_priorities($lessonmodule->id));

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        $now = 100;
        $override1 = (object)[
            'lessonid' => $lessonmodule->id,
            'groupid' => $group1->id,
            'available' => $now,
            'deadline' => $now + 20
        ];
        $DB->insert_record('lesson_overrides', $override1);

        $override2 = (object)[
            'lessonid' => $lessonmodule->id,
            'groupid' => $group2->id,
            'available' => $now - 10,
            'deadline' => $now + 10
        ];
        $DB->insert_record('lesson_overrides', $override2);

        $priorities = lesson_get_group_override_priorities($lessonmodule->id);
        $this->assertNotEmpty($priorities);

        $openpriorities = $priorities['open'];
        // Override 2's time open has higher priority since it is sooner than override 1's.
        $this->assertEquals(1, $openpriorities[$override1->available]);
        $this->assertEquals(2, $openpriorities[$override2->available]);

        $closepriorities = $priorities['close'];
        // Override 1's time close has higher priority since it is later than override 2's.
        $this->assertEquals(2, $closepriorities[$override1->deadline]);
        $this->assertEquals(1, $closepriorities[$override2->deadline]);
    }

    /**
     * Test the callback responsible for returning the completion rule descriptions.
     * This function should work given either an instance of the module (cm_info), such as when checking the active rules,
     * or if passed a stdClass of similar structure, such as when checking the the default completion settings for a mod type.
     */
    public function test_mod_lesson_completion_get_active_rule_descriptions() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 2]);
        $lesson1 = $this->getDataGenerator()->create_module('lesson', [
            'course' => $course->id,
            'completion' => 2,
            'completionendreached' => 1,
            'completiontimespent' => 3600
        ]);
        $lesson2 = $this->getDataGenerator()->create_module('lesson', [
            'course' => $course->id,
            'completion' => 2,
            'completionendreached' => 0,
            'completiontimespent' => 0
        ]);
        $cm1 = cm_info::create(get_coursemodule_from_instance('lesson', $lesson1->id));
        $cm2 = cm_info::create(get_coursemodule_from_instance('lesson', $lesson2->id));

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = new stdClass();
        $moddefaults->customdata = ['customcompletionrules' => [
            'completionendreached' => 1,
            'completiontimespent' => 3600
        ]];
        $moddefaults->completion = 2;

        $activeruledescriptions = [
            get_string('completionendreached_desc', 'lesson'),
            get_string('completiontimespentdesc', 'lesson', format_time(3600)),
        ];
        $this->assertEquals(mod_lesson_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
        $this->assertEquals(mod_lesson_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_lesson_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        $this->assertEquals(mod_lesson_get_completion_active_rule_descriptions(new stdClass()), []);
    }
}
