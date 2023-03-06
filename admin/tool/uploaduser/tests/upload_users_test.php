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

namespace tool_uploaduser;

use advanced_testcase;
use context_system;
use context_course;
use context_coursecat;
use tool_uploaduser\cli_helper;
use tool_uploaduser\local\text_progress_tracker;

/**
 * Class upload_users_test
 *
 * @package    tool_uploaduser
 * @copyright  2020 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_users_test extends advanced_testcase {

    /**
     * Load required test libraries
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        require_once("{$CFG->dirroot}/{$CFG->admin}/tool/uploaduser/locallib.php");
        require_once("{$CFG->dirroot}/completion/completion_completion.php");
    }

    /**
     * @covers \tool_uploadusers::process
     */
    public function test_user_can_upload_with_course_enrolment(): void {

        $this->resetAfterTest();
        set_config('passwordpolicy', 0);
        $this->setAdminUser();

        // Create category and course.
        $coursecat = $this->getDataGenerator()->create_category();
        $coursecatcontext = context_coursecat::instance($coursecat->id);
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat->id]);
        $coursecontext = context_course::instance($course->id);

        // Create user.
        $user = $this->getDataGenerator()->create_user();

        // Create role with capability to upload CSV files, and assign this role to user.
        $uploadroleid = create_role('upload role', 'uploadrole', '');
        set_role_contextlevels($uploadroleid, [CONTEXT_SYSTEM]);
        $systemcontext = context_system::instance();
        assign_capability('moodle/site:uploadusers', CAP_ALLOW, $uploadroleid, $systemcontext->id);
        $this->getDataGenerator()->role_assign($uploadroleid, $user->id, $systemcontext->id);
        accesslib_clear_all_caches_for_unit_testing();

        // Create role with some of allowed capabilities to enrol users, and assign this role to user.
        $enrolroleid = create_role('enrol role', 'enrolrole', '');
        set_role_contextlevels($enrolroleid, [CONTEXT_COURSECAT]);
        assign_capability('enrol/manual:enrol', CAP_ALLOW, $enrolroleid, $coursecatcontext->id);
        assign_capability('moodle/course:enrolreview', CAP_ALLOW, $enrolroleid, $coursecatcontext->id);
        assign_capability('moodle/role:assign', CAP_ALLOW, $enrolroleid, $coursecatcontext->id);
        $this->getDataGenerator()->role_assign($enrolroleid, $user->id, $coursecatcontext->id);
        accesslib_clear_all_caches_for_unit_testing();

        // User makes assignments.
        $studentarch = get_archetype_roles('student');
        $studentrole = array_shift($studentarch);
        role_assign($studentrole->id, $user->id, $coursecatcontext->id);
        accesslib_clear_all_caches_for_unit_testing();

        $assignablerole = get_assignable_roles($coursecatcontext, ROLENAME_SHORT); // DELETEME

        $csv = <<<EOF
username,firstname,lastname,email,course1,role1
student1,Student,One,s1@example.com,{$course->shortname},{$studentrole->shortname}
student2,Student,Two,s2@example.com,{$course->shortname},{$studentrole->shortname}
EOF;

        // Process CSV file as user.
        $this->setUser($user);

        $assignablerole = get_assignable_roles($coursecatcontext, ROLENAME_SHORT); // DELETEME

        $output = $this->process_csv_upload($csv, ['--uutype=' . UU_USER_ADDNEW]);
        $this->assertStringNotContainsString('Error', $output);

        // Check user creation and enrolment. // TODO!!!!!!!!!!!!!!!!!!!!!!!1
        $enrolledusers = get_enrolled_users($coursecontext);
        $users = get_role_users($studentrole->id, $coursecontext);

    }

    /**
     * Generate cli_helper and mock $_SERVER['argv']
     *
     * @param string $filecontent
     * @param array $mockargv
     * @return string
     */
    protected function process_csv_upload(string $filecontent, array $mockargv = []): string {
        $filepath = make_request_directory() . '/upload.csv';
        file_put_contents($filepath, $filecontent);
        $mockargv[] = "--file={$filepath}";

        if (array_key_exists('argv', $_SERVER)) {
            $oldservervars = $_SERVER['argv'];
        }

        $_SERVER['argv'] = array_merge([''], $mockargv);
        $clihelper = new cli_helper(text_progress_tracker::class);

        if (isset($oldservervars)) {
            $_SERVER['argv'] = $oldservervars;
        } else {
            unset($_SERVER['argv']);
        }

        ob_start();
        $clihelper->process();
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
