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
 * Questions editlib tests
 *
 * @package core_question
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Questions editlib tests
 *
 * @package core_question
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_question_editlib_testcase extends advanced_testcase {

    /**
     * Creates a question with automatic unique name
     *
     * @param int $catid question category id
     * @param array|string $tags
     * @return stdClass
     */
    protected function create_question($catid, $tags = null) {
        static $cnt = 0;

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        return $generator->create_question('shortanswer', null,
                array('category' => $catid, 'tags' => $tags, 'name' => 'Question '.(++$cnt)));
    }

    /**
     * Test function question_get_tagged_questions() - searching tagged questions
     */
    public function test_question_get_tagged_questions() {
        global $DB;
        $this->resetAfterTest();

        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('quiz', array('course' => $course2));
        $cat = $this->getDataGenerator()->create_category();

        $context1 = context_course::instance($course1->id);
        $cat1 = $generator->create_question_category(array('contextid' => $context1->id));
        $context2 = context_course::instance($course2->id);
        $cat2 = $generator->create_question_category(array('contextid' => $context2->id));
        $context3 = context_module::instance($module->cmid);
        $cat3 = $generator->create_question_category(array('contextid' => $context3->id));
        $context4 = context_coursecat::instance($cat->id);
        $cat4 = $generator->create_question_category(array('contextid' => $context4->id));

        $question11 = $this->create_question($cat1->id);
        $question12 = $this->create_question($cat1->id, array('Cats', 'dogs'));
        $question13 = $this->create_question($cat1->id, 'mice, Cats');
        $question21 = $this->create_question($cat2->id, 'Cats');
        $question22 = $this->create_question($cat2->id, 'Cats, dogs');
        $question23 = $this->create_question($cat2->id, 'Cats, dogs,mice');
        $question24 = $this->create_question($cat2->id, 'Cats, mice');
        $question31 = $this->create_question($cat3->id, 'Cats, mice');
        $question41 = $this->create_question($cat4->id, 'Cats, mice');

        // Admin can see everything.
        $tag = core_tag_tag::get_by_name(0, 'Cats');
        $res = question_get_tagged_questions($tag, /*$exclusivemode = */false, /*$fromctx = */0,
                /*$ctx = */0, /*$rec = */1, /*$page = */0);
        $this->assertRegExp('/'.$question41->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question11->name.'/', $res->content);
        $this->assertRegExp('/'.$question12->name.'/', $res->content);
        $this->assertRegExp('/'.$question13->name.'/', $res->content);
        $this->assertRegExp('/'.$question21->name.'/', $res->content);
        $this->assertRegExp('/'.$question22->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question23->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question24->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question31->name.'/', $res->content);
        $this->assertEmpty($res->prevpageurl);
        $this->assertNotEmpty($res->nextpageurl);
        $res = question_get_tagged_questions($tag, /*$exclusivemode = */false, /*$fromctx = */0,
                /*$ctx = */0, /*$rec = */1, /*$page = */1);
        $this->assertNotRegExp('/'.$question41->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question11->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question12->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question13->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question21->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question22->name.'/', $res->content);
        $this->assertRegExp('/'.$question23->name.'/', $res->content);
        $this->assertRegExp('/'.$question24->name.'/', $res->content);
        $this->assertRegExp('/'.$question31->name.'/', $res->content);
        $this->assertNotEmpty($res->prevpageurl);
        $this->assertEmpty($res->nextpageurl);

        // Create and enrol a user.
        $user = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, $teacherrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, $teacherrole->id, 'manual');
        $this->setUser($user);
        core_tag_index_builder::reset_caches();

        // Teacher can search the questions globally (only visible will be returned).
        $res = question_get_tagged_questions($tag, /*$exclusivemode = */true,
                /*$fromctx = */0, /*$ctx = */0, /*$rec = */1, /*$page = */0);
        $this->assertNotRegExp('/'.$question11->name.'/', $res->content);
        $this->assertRegExp('/'.$question12->name.'/', $res->content);
        $this->assertRegExp('/'.$question13->name.'/', $res->content);
        $this->assertRegExp('/'.$question21->name.'/', $res->content);
        $this->assertRegExp('/'.$question22->name.'/', $res->content);
        $this->assertRegExp('/'.$question23->name.'/', $res->content);
        $this->assertRegExp('/'.$question24->name.'/', $res->content);
        $this->assertRegExp('/'.$question31->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question41->name.'/', $res->content);
        $this->assertEmpty($res->nextpageurl);

        // User can search questions inside a course.
        $coursecontext = context_course::instance($course1->id);
        $res = question_get_tagged_questions($tag, /*$exclusivemode = */false,
                /*$fromctx = */0, /*$ctx = */$coursecontext->id, /*$rec = */1, /*$page = */0);
        $this->assertNotRegExp('/'.$question11->name.'/', $res->content);
        $this->assertRegExp('/'.$question12->name.'/', $res->content);
        $this->assertRegExp('/'.$question13->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question21->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question22->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question23->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question24->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question31->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question41->name.'/', $res->content);
        $this->assertEmpty($res->nextpageurl);

        // Manager in category can see questions in the category.
        $manager = self::getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id, $context4);
        $this->setUser($manager);
        core_tag_index_builder::reset_caches();

        $res = question_get_tagged_questions($tag, /*$exclusivemode = */true,
            /*$fromctx = */0, /*$ctx = */0, /*$rec = */1, /*$page = */0);
        $this->assertNotRegExp('/'.$question11->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question12->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question13->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question21->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question22->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question23->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question24->name.'/', $res->content);
        $this->assertNotRegExp('/'.$question31->name.'/', $res->content);
        $this->assertRegExp('/'.$question41->name.'/', $res->content);
    }
}
