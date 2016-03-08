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
 * Data generators tests
 *
 * @package    moodlecore
 * @subpackage questionengine
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test data generator
 *
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_question_generator_testcase extends advanced_testcase {
    public function test_create_question_category() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $count = $DB->count_records('question_categories');

        $cat = $generator->create_question_category();
        $this->assertEquals($count + 1, $DB->count_records('question_categories'));

        $cat = $generator->create_question_category(array(
                'name' => 'My category', 'sortorder' => 1));
        $this->assertSame('My category', $cat->name);
        $this->assertSame(1, $cat->sortorder);
    }

    public function test_create_question() {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $cat = $generator->create_question_category(array('contextid' => $context->id));

        $question1 = $generator->create_question('shortanswer', null,
                array('category' => $cat->id));
        $question2 = $generator->create_question('shortanswer', null,
                array('category' => $cat->id, 'tags' => array('Cats', 'dogs')));
        $question3 = $generator->create_question('shortanswer', null,
                array('category' => $cat->id, 'tags' => 'mice, Cats'));

        $tags = array_values(core_tag_tag::get_item_tags_array('core_question', 'question', $question2->id));
        $this->assertEquals(array('Cats', 'dogs'), $tags);
        $tags = array_values(core_tag_tag::get_item_tags_array('core_question', 'question', $question3->id));
        $this->assertEquals(array('mice', 'Cats'), $tags);
    }
}
