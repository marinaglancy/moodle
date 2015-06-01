<?php

/**
 * Unit tests for user/selector/lib.php.
 *
 * @package    core_user
 * @category   phpunit
 * @copyright  2015 PC <rurouni88@gmail.com>
 * @license    To be determined
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/user/selector/lib.php');

class core_user_selector_lib_testcase extends advanced_testcase {
    function test_print_user_summaries() {
        global $CFG;
        parent::setup();

        $this->resetAfterTest(true);

        // Setup Test. Create Course, Group, User and enrol user into course
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $group = $generator->create_group(array('courseid' => $course->id));

        $range_start = 0;
        $range_finish = 4;
        $num_users = 0;
        $users = array();
        foreach (range($range_start, $range_finish) as $number) {
            $user = $generator->create_user();
            $generator->enrol_user($user->id, $course->id);
            array_push($users, $user);
            $num_users++;
        }

        // Test: Ensure that we have $num_users in usersummaries
        $instance = new group_non_members_selector('addselect', array('groupid' => $group->id, 'courseid' => $course->id));

        // Quirk: Need to run find_users() to populate $instance->potentialmembersids
        // Could have optionally mocked this out
        $instance->find_users('');
        $usersummaries = $instance->print_user_summaries($course->id);

        $this->assertTrue(is_array($usersummaries));
        $this->assertCount($num_users, $usersummaries);
        foreach (range($range_start, $range_finish) as $number) {
            $this->assertEmpty($usersummaries[$users[$number]->id]);
            $this->assertArrayHasKey($users[$number]->id, $usersummaries);
            $this->assertTrue(is_array($usersummaries[$users[$number]->id]));
        }

        // Test: Ensure that adding a member to the group results
        // in usersummaries[user_id] not being empty
        $random_number = rand ($range_start, $range_finish);
        $generator->create_group_member(array('groupid' => $group->id, 'userid' => $users[$random_number]->id));

        $instance->find_users('');
        $usersummaries = $instance->print_user_summaries($course->id);

        $this->assertTrue(is_array($usersummaries));
        $this->assertCount($num_users, $usersummaries);

        $this->assertTrue(is_array($usersummaries[$users[$random_number]->id]));
        $this->assertTrue(count($usersummaries[$users[$random_number]->id]) == 1);

        return;
    }
}
