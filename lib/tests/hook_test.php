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
 * Tests for hook manager, base class and callbacks.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/hook_fixtures.php');

/**
 * Tests for hook manager, base class and callbacks.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_hook_testcase extends advanced_testcase {

    /**
     * Test parsing callbacks lists
     */
    public function test_callbacks_parsing() {
        global $CFG;

        $callbacks = array(
            array(
                'hookname'    => '\core_tests\hook\unittest_executed',
                'callback'    => '\core_tests\hook\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
            array(
                'hookname' => '\core\hook\unknown_executed',
                'callback' => '\core_tests\hook\unittest_callback::broken_callback',
                'priority' => 100,
            ),
            array(
                'hookname' => '\core_tests\hook\unittest_executed',
                'callback' => '\core_tests\hook\unittest_callback::observe_two',
                'priority' => 200,
            ),
        );

        $result = \core\hook\manager::phpunit_replace_callbacks($callbacks);
        $this->assertCount(2, $result);

        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\hook\unittest_callback::observe_two';
        $callback->priority = 200;
        $callback->includefile = null;
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;
        $callback = new stdClass();
        $callback->callable = '\core_tests\hook\unittest_callback::observe_one';
        $callback->priority = 0;
        $callback->includefile = $CFG->dirroot.'/lib/tests/fixtures/hook_fixtures.php';
        $callback->component = 'core_phpunit';
        $expected[1] = $callback;

        $this->assertEquals($expected, $result['\core_tests\hook\unittest_executed']);

        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\hook\unittest_callback::broken_callback';
        $callback->priority = 100;
        $callback->includefile = null;
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;

        $this->assertEquals($expected, $result['\core\hook\unknown_executed']);

        // Now test broken stuff...

        $callbacks = array(
            array(
                'hookname'    => 'core_tests\hook\unittest_executed', // Fix leading backslash.
                'callback'    => '\core_tests\hook\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\manager::phpunit_replace_callbacks($callbacks);
        $this->assertCount(1, $result);
        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\hook\unittest_callback::observe_one';
        $callback->priority = 0;
        $callback->includefile = $CFG->dirroot.'/lib/tests/fixtures/hook_fixtures.php';
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;
        $this->assertEquals($expected, $result['\core_tests\hook\unittest_executed']);

        $callbacks = array(
            array(
                // Missing hookclass.
                'callback'    => '\core_tests\hook\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\manager::phpunit_replace_callbacks($callbacks);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $callbacks = array(
            array(
                'hookname'    => '', // Empty hookclass.
                'callback'    => '\core_tests\hook\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\manager::phpunit_replace_callbacks($callbacks);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $callbacks = array(
            array(
                'hookname'    => '\core_tests\hook\unittest_executed',
                // Missing callable.
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\manager::phpunit_replace_callbacks($callbacks);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $callbacks = array(
            array(
                'hookname'    => '\core_tests\hook\unittest_executed',
                'callback'    => '', // Empty callable.
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\manager::phpunit_replace_callbacks($callbacks);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $callbacks = array(
            array(
                'hookname'    => '\core_tests\hook\unittest_executed',
                'callback'    => '\core_tests\hook\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php_xxx', // Missing file.
            ),
        );
        $result = \core\hook\manager::phpunit_replace_callbacks($callbacks);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();
    }

    /**
     * Test situations when one of callbacks throws an exception.
     */
    public function test_callbacks_exceptions() {
        $callbacks = array(

            array(
                'hookname' => '\core_tests\hook\unittest_executed',
                'callback' => '\core_tests\hook\unittest_callback::observe_one',
            ),

            array(
                'hookname' => '\core_tests\hook\unittest_executed',
                'callback' => '\core_tests\hook\unittest_callback::broken_callback',
                'priority' => 100,
            ),
        );

        \core\hook\manager::phpunit_replace_callbacks($callbacks);
        \core_tests\hook\unittest_callback::reset();

        // Execute ignoring exceptions.
        $hook1 = \core_tests\hook\unittest_executed::create((object)array('id' => 1, 'name' => 'something'));
        $hook1->execute();
        $this->assertDebuggingCalled();

        // Assert that both callbacks were executed even though the first one threw exception.
        $this->assertSame(
            array('broken_callback-1', 'observe_one-1'),
            \core_tests\hook\unittest_callback::$info);

        // Execute throwing exceptions.
        \core_tests\hook\unittest_callback::reset();
        $hook1 = \core_tests\hook\unittest_executed::create((object)array('id' => 2, 'name' => 'something'));
        try {
            $hook1->execute(null, true);
            $this->fail('Exception expected');
        } catch (Exception $e) {
            $this->assertEquals('someerror', $e->getMessage());
        }

        // Assert that only first callback was executed and then execution stopped because of exception.
        $this->assertSame(
            array('broken_callback-2'),
            \core_tests\hook\unittest_callback::$info);
    }

    /**
     * Test executing hook for one component only.
     */
    public function test_execute_for_component() {
        $callbacks = array(
            array(
                'hookname' => '\core_tests\hook\unittest_executed',
                'callback' => '\core_tests\hook\unittest_callback::observe_one',
            ),
        );
        \core\hook\manager::phpunit_replace_callbacks($callbacks);

        // Execute hook for the component 'core_phpunit'.
        \core_tests\hook\unittest_callback::reset();
        \core_tests\hook\unittest_executed::create((object)array('id' => 1))->execute('core_phpunit');
        $this->assertSame(
            array('observe_one-1'),
            \core_tests\hook\unittest_callback::$info);

        // Execute hook for another component.
        \core_tests\hook\unittest_callback::reset();
        \core_tests\hook\unittest_executed::create((object)array('id' => 2))->execute('tool_anothercomponent');
        $this->assertEmpty(\core_tests\hook\unittest_callback::$info);
    }

    /**
     * Test executing hook recursively.
     */
    public function test_execute_recursive() {
        $callbacks = array(
            array(
                'hookname' => '\core_tests\hook\unittest_executed',
                'callback' => '\core_tests\hook\unittest_callback::recursive_callback1',
            ),
        );
        \core\hook\manager::phpunit_replace_callbacks($callbacks);
        \core_tests\hook\unittest_callback::reset();

        // Execute hook.
        \core_tests\hook\unittest_executed::create((object)array('id' => 1))->execute();
        $this->assertDebuggingCalled('hook is already being executed');
        $this->assertSame(
            array('recursive_callback1-1'),
            \core_tests\hook\unittest_callback::$info);

        // Another recursive callback.
        $callbacks = array(
            array(
                'hookname' => '\core_tests\hook\unittest_executed',
                'callback' => '\core_tests\hook\unittest_callback::recursive_callback2',
            ),
        );
        \core\hook\manager::phpunit_replace_callbacks($callbacks);
        \core_tests\hook\unittest_callback::reset();

        // Execute hook.
        \core_tests\hook\unittest_executed::create((object)array('id' => 1))->execute();
        $this->assertDebuggingCalled('hook is already being executed');
        $this->assertSame(
            array('recursive_callback2-1'),
            \core_tests\hook\unittest_callback::$info);
    }
}
