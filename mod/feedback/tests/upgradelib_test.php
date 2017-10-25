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
 * Tests for functions in db/upgradelib.php
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/feedback/db/upgradelib.php');

/**
 * Tests for functions in db/upgradelib.php
 *
 * @package    mod_feedback
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_upgradelib_testcase extends advanced_testcase {

    /** @var string  */
    protected $testsql = "SELECT COUNT(v.id) FROM {feedback_completed} c, {feedback_value} v
            WHERE c.id = v.completed AND c.courseid <> v.course_id";
    /** @var string  */
    protected $testsqltmp = "SELECT COUNT(v.id) FROM {feedback_completedtmp} c, {feedback_valuetmp} v
            WHERE c.id = v.completed AND c.courseid <> v.course_id";
    /** @var int */
    protected $course1;
    /** @var int */
    protected $course2;
    /** @var stdClass */
    protected $feedback;
    /** @var stdClass */
    protected $user;

    /**
     * Sets up the fixture
     * This method is called before a test is executed.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);

        $this->course1 = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();
        $this->feedback = $this->getDataGenerator()->create_module('feedback', array('course' => SITEID));

        $this->user = $this->getDataGenerator()->create_user();
    }

    public function test_upgrade_courseid_completed() {
        global $DB;

        // Case 1. No errors in the data.
        $completed1 = $DB->insert_record('feedback_completed',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
            'item' => 1, 'value' => 1]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
            'item' => 2, 'value' => 2]);

        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $this->assertEquals(2, $DB->count_records_sql($this->testsql)); // We have errors!
        mod_feedback_upgrade_courseid(true); // Running script for temp tables.
        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $this->assertEquals(2, $DB->count_records_sql($this->testsql)); // Nothing changed.
        mod_feedback_upgrade_courseid();
        $this->assertCount(1, $DB->get_records('feedback_completed')); // Number of records is the same.
        $this->assertEquals(0, $DB->count_records_sql($this->testsql)); // All errors are fixed!
    }

    public function test_upgrade_courseid_completed_with_errors() {
        global $DB;

        // Case 2. Errors in data (same feedback_completed has values for different courses).
        $completed1 = $DB->insert_record('feedback_completed',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
            'item' => 1, 'value' => 1]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course2->id,
            'item' => 1, 'value' => 2]);

        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $this->assertEquals(2, $DB->count_records_sql($this->testsql)); // We have errors!
        mod_feedback_upgrade_courseid(true); // Running script for temp tables.
        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $this->assertEquals(2, $DB->count_records_sql($this->testsql)); // Nothing changed.
        mod_feedback_upgrade_courseid();
        $this->assertCount(2, $DB->get_records('feedback_completed')); // Extra record inserted.
        $this->assertEquals(0, $DB->count_records_sql($this->testsql)); // All errors are fixed!
    }

    public function test_upgrade_courseid_completedtmp() {
        global $DB;

        // Case 1. No errors in the data.
        $completed1 = $DB->insert_record('feedback_completedtmp',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
            'item' => 1, 'value' => 1]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
            'item' => 2, 'value' => 2]);

        $this->assertCount(1, $DB->get_records('feedback_completedtmp'));
        $this->assertEquals(2, $DB->count_records_sql($this->testsqltmp)); // We have errors!
        mod_feedback_upgrade_courseid(); // Running script for non-temp tables.
        $this->assertCount(1, $DB->get_records('feedback_completedtmp'));
        $this->assertEquals(2, $DB->count_records_sql($this->testsqltmp)); // Nothing changed.
        mod_feedback_upgrade_courseid(true);
        $this->assertCount(1, $DB->get_records('feedback_completedtmp')); // Number of records is the same.
        $this->assertEquals(0, $DB->count_records_sql($this->testsqltmp)); // All errors are fixed!
    }

    public function test_upgrade_courseid_completedtmp_with_errors() {
        global $DB;

        // Case 2. Errors in data (same feedback_completed has values for different courses).
        $completed1 = $DB->insert_record('feedback_completedtmp',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
            'item' => 1, 'value' => 1]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course2->id,
            'item' => 1, 'value' => 2]);

        $this->assertCount(1, $DB->get_records('feedback_completedtmp'));
        $this->assertEquals(2, $DB->count_records_sql($this->testsqltmp)); // We have errors!
        mod_feedback_upgrade_courseid(); // Running script for non-temp tables.
        $this->assertCount(1, $DB->get_records('feedback_completedtmp'));
        $this->assertEquals(2, $DB->count_records_sql($this->testsqltmp)); // Nothing changed.
        mod_feedback_upgrade_courseid(true);
        $this->assertCount(2, $DB->get_records('feedback_completedtmp')); // Extra record inserted.
        $this->assertEquals(0, $DB->count_records_sql($this->testsqltmp)); // All errors are fixed!
    }

    public function test_upgrade_courseid_empty_completed() {
        global $DB;

        // Record in 'feedback_completed' does not have corresponding values.
        $DB->insert_record('feedback_completed',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);

        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $record1 = $DB->get_record('feedback_completed', []);
        mod_feedback_upgrade_courseid();
        $this->assertCount(1, $DB->get_records('feedback_completed')); // Number of records is the same.
        $record2 = $DB->get_record('feedback_completed', []);
        $this->assertEquals($record1, $record2);
    }

    public function test_upgrade_remove_duplicates_no_duplicates() {
        global $DB;

        $completed1 = $DB->insert_record('feedback_completed',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 1, 'value' => 1]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 2, 'value' => 2]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 3, 'value' => 1]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course2->id,
                'item' => 3, 'value' => 2]);

        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $this->assertEquals(4, $DB->count_records('feedback_value'));
        mod_feedback_upgrade_delete_duplicate_values();
        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $this->assertEquals(4, $DB->count_records('feedback_value')); // Same number of records, no changes made.
    }

    public function test_upgrade_remove_duplicates() {
        global $DB;

        // Remove the index that was added in the upgrade.php AFTER running mod_feedback_upgrade_delete_duplicate_values().
        $dbman = $DB->get_manager();
        $table = new xmldb_table('feedback_value');
        $index = new xmldb_index('completed_item', XMLDB_INDEX_UNIQUE, array('completed', 'item', 'course_id'));
        $dbman->drop_index($table, $index);

        // Insert duplicated values.
        $completed1 = $DB->insert_record('feedback_completed',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 1, 'value' => 1]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 1, 'value' => 2]); // This is a duplicate with another value.
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 3, 'value' => 1]);
        $DB->insert_record('feedback_value',
            ['completed' => $completed1, 'course_id' => $this->course2->id,
                'item' => 3, 'value' => 2]); // This is not a duplicate because course id is different.

        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $this->assertEquals(4, $DB->count_records('feedback_value'));
        mod_feedback_upgrade_delete_duplicate_values(true); // Running script for temp tables.
        $this->assertCount(1, $DB->get_records('feedback_completed'));
        $this->assertEquals(4, $DB->count_records('feedback_value')); // Nothing changed.
        mod_feedback_upgrade_delete_duplicate_values();
        $this->assertCount(1, $DB->get_records('feedback_completed')); // Number of records is the same.
        $this->assertEquals(3, $DB->count_records('feedback_value')); // Duplicate was deleted.
        $this->assertEquals(1, $DB->get_field('feedback_value', 'value', ['item' => 1]));

        $dbman->add_index($table, $index);
    }

    public function test_upgrade_remove_duplicates_no_duplicates_tmp() {
        global $DB;

        $completed1 = $DB->insert_record('feedback_completedtmp',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 1, 'value' => 1]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 2, 'value' => 2]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 3, 'value' => 1]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course2->id,
                'item' => 3, 'value' => 2]);

        $this->assertCount(1, $DB->get_records('feedback_completedtmp'));
        $this->assertEquals(4, $DB->count_records('feedback_valuetmp'));
        mod_feedback_upgrade_delete_duplicate_values(true);
        $this->assertCount(1, $DB->get_records('feedback_completedtmp'));
        $this->assertEquals(4, $DB->count_records('feedback_valuetmp')); // Same number of records, no changes made.
    }

    public function test_upgrade_remove_duplicates_tmp() {
        global $DB;

        // Remove the index that was added in the upgrade.php AFTER running mod_feedback_upgrade_delete_duplicate_values().
        $dbman = $DB->get_manager();
        $table = new xmldb_table('feedback_valuetmp');
        $index = new xmldb_index('completed_item', XMLDB_INDEX_UNIQUE, array('completed', 'item', 'course_id'));
        $dbman->drop_index($table, $index);

        // Insert duplicated values.
        $completed1 = $DB->insert_record('feedback_completedtmp',
            ['feedback' => $this->feedback->id, 'userid' => $this->user->id]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 1, 'value' => 1]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 1, 'value' => 2]); // This is a duplicate with another value.
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course1->id,
                'item' => 3, 'value' => 1]);
        $DB->insert_record('feedback_valuetmp',
            ['completed' => $completed1, 'course_id' => $this->course2->id,
                'item' => 3, 'value' => 2]); // This is not a duplicate because course id is different.

        $this->assertCount(1, $DB->get_records('feedback_completedtmp'));
        $this->assertEquals(4, $DB->count_records('feedback_valuetmp'));
        mod_feedback_upgrade_delete_duplicate_values(); // Running script for non-temp tables.
        $this->assertCount(1, $DB->get_records('feedback_completedtmp'));
        $this->assertEquals(4, $DB->count_records('feedback_valuetmp')); // Nothing changed.
        mod_feedback_upgrade_delete_duplicate_values(true);
        $this->assertCount(1, $DB->get_records('feedback_completedtmp')); // Number of records is the same.
        $this->assertEquals(3, $DB->count_records('feedback_valuetmp')); // Duplicate was deleted.
        $this->assertEquals(1, $DB->get_field('feedback_valuetmp', 'value', ['item' => 1]));

        $dbman->add_index($table, $index);
    }

    /**
     * Creates a one page feedback with several items
     *
     * @param int $feedbackcourseid
     * @param int $anonymous
     * @param bool $multipleattempts
     * @return array
     */
    protected function create_simple_feedback($feedbackcourseid, $anonymous = FEEDBACK_ANONYMOUS_YES, $multipleattempts = false) {
        $generator = $this->getDataGenerator();
        $feedback = $generator->create_module('feedback',
            array('course' => $feedbackcourseid, 'anonymous' => $anonymous, 'multiple_submit' => (int)(bool)$multipleattempts));
        $feedbackgenerator = $this->getDataGenerator()->get_plugin_generator('mod_feedback');
        $item1 = $feedbackgenerator->create_item_textfield($feedback);
        $item2 = $feedbackgenerator->create_item_numeric($feedback);

        return ['feedback' => $feedback, 'items' => [$item1, $item2]];
    }

    /**
     * Complete feedback using web service function (it will be identical to submitting the form).
     *
     * @param stdClass $user
     * @param array $feedbackdata result of {@link create_simple_feedback}
     * @param array $values student responses
     * @param int $courseid course where student submits feedback
     * @return stdClass record from table {feedback_completed}
     */
    protected function submit_simple_feedback($user, $feedbackdata, $values, $courseid = 0) {
        $this->setUser($user);
        list($course, $cm) = get_course_and_cm_from_instance($feedbackdata['feedback']->id, 'feedback');
        $feedbackcompletion = new mod_feedback_completion($feedbackdata['feedback'], $cm, $courseid);
        $pagedata = [];
        foreach ($feedbackdata['items'] as $i => $item) {
            $pagedata[$item->typ .'_'. $item->id] = $values[$i];
        }
        $feedbackcompletion->save_response_tmp((object)$pagedata);
        $feedbackcompletion->save_response();
        return $feedbackcompletion->get_completed();
    }

    /**
     * @param int $feedbackcourseid course where feedback should be created - either SITEID or $this->course1->id
     * @param int $courseid course where users submit their feedback (if $feedbackcourseid==SITEID this can be $this->course1->id)
     * @return array
     */
    protected function prepare_upgrade_restore_removed_completed($feedbackcourseid, $courseid = 0) {
        global $DB;

        $generator = $this->getDataGenerator();

        // Create an anonymous feedback that allows multiple submissions -
        // even if we need a different type of feedback later, this is the only way how we can
        // create multiple submissions at this stage.
        $feedbackdata = $this->create_simple_feedback($feedbackcourseid, FEEDBACK_ANONYMOUS_YES, true);

        // Create two users.
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        // Enrol them as students into the course where they submit a feedback.
        $usercourseid = ($courseid && $courseid != SITEID) ? $courseid : $feedbackcourseid;
        if ($usercourseid && $usercourseid != SITEID) {
            $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
            $generator->enrol_user($student1->id, $usercourseid, $roleids['student']);
            $generator->enrol_user($student2->id, $usercourseid, $roleids['student']);
        }

        // Create two submissions each for this course.
        $completed11 = $this->submit_simple_feedback($student1, $feedbackdata, ['first', 1], $courseid);
        $completed12 = $this->submit_simple_feedback($student1, $feedbackdata, ['second', 2], $courseid);
        $completed21 = $this->submit_simple_feedback($student2, $feedbackdata, ['third', 3], $courseid);
        $completed22 = $this->submit_simple_feedback($student2, $feedbackdata, ['fourth', 4], $courseid);

        // Now remove first submissions (record in feedback_completed) as the old version of upgrade from 28 March 2018 script would do.
        $sql = "SELECT MAX(id) as maxid, userid, feedback, courseid
                  FROM {feedback_completed}
                 WHERE userid <> 0
              GROUP BY userid, feedback, courseid
                HAVING COUNT(id) > 1";

        $duplicatedrows = $DB->get_recordset_sql($sql);
        foreach ($duplicatedrows as $row) {
            $DB->delete_records_select('feedback_completed', 'userid = ? AND feedback = ? AND courseid = ? AND id <> ?', array(
                $row->userid,
                $row->feedback,
                $row->courseid,
                $row->maxid,
            ));
        }
        $duplicatedrows->close();

        // Double check that record 11 and 21 were removed.
        $this->assertFalse($DB->record_exists('feedback_completed', ['id' => $completed11->id]));
        $this->assertTrue($DB->record_exists('feedback_completed', ['id' => $completed12->id]));
        $this->assertFalse($DB->record_exists('feedback_completed', ['id' => $completed21->id]));
        $this->assertTrue($DB->record_exists('feedback_completed', ['id' => $completed22->id]));

        return [$feedbackdata['feedback'], $completed11, $completed12, $completed21, $completed22];
    }

    /**
     * Enable logging of events in logstore_standard_log table
     */
    protected function enable_logging() {
        $this->preventResetByRollback();
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        set_config('buffersize', 0, 'logstore_standard');
        set_config('logguests', 1, 'logstore_standard');
        get_log_manager(true);
    }

    /**
     * Asserts that restored record in feedback_completed is the same  as the original one
     *
     * - property timemodified may be off by 1-2 seconds
     *
     * @param stdClass $expected
     * @param stdClass $actual
     */
    protected function assert_feedback_completed($expected, $actual) {
        foreach ($expected as $key => $value) {
            if ($key === 'timemodified') {
                $this->assertTrue(abs($actual->$key - $value) < 3, 'Field '.$key.' does not match');
            } else {
                $this->assertEquals($value, $actual->$key, 'Field '.$key.' does not match');
            }
        }
    }

    /**
     * Data provider for test_upgrade_http_links
     */
    public function restore_removed_completed_provider() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/feedback/lib.php');
        return [
            "Removed completions from anonymous & multiple_submit feedback are restored (using logs with logs avialable)" => [
                "feedbackparams" => ['anonymous' => FEEDBACK_ANONYMOUS_YES, 'multiple_submit' => 1],
                "enablelogs" => true,
                "uselogs" => true,
                "isrestored" => "exact"
            ],
            "Removed completions from anonymous & multiple_submit feedback are restored (using logs with logs not available)" => [
                "feedbackparams" => ['anonymous' => FEEDBACK_ANONYMOUS_YES, 'multiple_submit' => 1],
                "enablelogs" => false,
                "uselogs" => true,
                "isrestored" => "almost"
            ],
            "Removed completions from anonymous & multiple_submit feedback are restored (not using logs)" => [
                "feedbackparams" => ['anonymous' => FEEDBACK_ANONYMOUS_YES, 'multiple_submit' => 1],
                "enablelogs" => true,
                "uselogs" => false,
                "isrestored" => "almost"
            ],
            "Removed completions from anonymous & non multiple_submit feedback are not restored" => [
                "feedbackparams" => ['anonymous' => FEEDBACK_ANONYMOUS_YES, 'multiple_submit' => 0],
                "enablelogs" => true,
                "uselogs" => true,
                "isrestored" => "no"
            ],
            "Removed completions from non anonymous feedback are not restored" => [
                "feedbackparams" => ['anonymous' => FEEDBACK_ANONYMOUS_NO],
                "enablelogs" => true,
                "uselogs" => true,
                "isrestored" => "no"
            ],
            "Removed completions from anonymous & multiple_submit feedback on front page are restored" => [
                "feedbackparams" => ['anonymous' => FEEDBACK_ANONYMOUS_YES, 'multiple_submit' => 1],
                "enablelogs" => true,
                "uselogs" => true,
                "isrestored" => "exact",
                'feedbackcourseid' => SITEID
            ],
        ];
    }

    /**
     * Testing mod_feedback_upgrade_restore_removed_completed
     *
     * @dataProvider restore_removed_completed_provider
     */
    public function test_upgrade_restore_removed_completed($feedbackparams, $enablelogs, $uselogs, $isrestored, $feedbackcourseid = 0) {
        global $DB;
        if ($enablelogs) {
            $this->enable_logging();
        }
        $feedbackcourseid = $feedbackcourseid ?: $this->course1->id;
        $courseid = ($feedbackcourseid == SITEID) ? $this->course1->id : 0; // For frontpage feedback let's complete it in a course.
        list($feedback, $completed11, $completed12, $completed21, $completed22) =
            $this->prepare_upgrade_restore_removed_completed($feedbackcourseid, $courseid);
        $DB->update_record('feedback', ['id' => $feedback->id] + $feedbackparams);

        if (!$uselogs) {
            // This disables querying logs in the
            set_config('upgrade20170328nolog', 1, 'mod_feedback');
        }

        mod_feedback_upgrade_restore_removed_completed();

        // Check that feedback_completed records were restored.
        $restored11 = $DB->get_record('feedback_completed', ['id' => $completed11->id]);
        $restored12 = $DB->get_record('feedback_completed', ['id' => $completed12->id]);
        $restored21 = $DB->get_record('feedback_completed', ['id' => $completed21->id]);
        $restored22 = $DB->get_record('feedback_completed', ['id' => $completed22->id]);
        if ($isrestored === 'no') {
            $this->assertFalse($DB->record_exists('feedback_completed', ['id' => $completed11->id]));
            $this->assertFalse($DB->record_exists('feedback_completed', ['id' => $completed21->id]));
        } else if ($isrestored === 'almost') {
            $expected11 = (object)(['userid' => 1, 'timemodified' => 1] + (array)$completed11);
            $expected21 = (object)(['userid' => 1, 'timemodified' => 1] + (array)$completed21);
            $this->assertEquals($expected11, $restored11);
            $this->assertEquals($expected21, $restored21);
        } else if ($isrestored === 'exact') {
            $this->assert_feedback_completed($completed11, $restored11);
            $this->assert_feedback_completed($completed21, $restored21);
        }
        // Last two submissions should never be changed.
        $this->assertEquals($completed12, $restored12);
        $this->assertEquals($completed22, $restored22);
    }
}