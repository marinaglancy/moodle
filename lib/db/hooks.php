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
 * Definition of core hooks callbacks.
 *
 * The callbacks defined in this file are notified when respective hooks are executed. All plugins
 * support this.
 *
 * For more information, take a look to the documentation available:
 *     - Hooks API: {@link http://docs.moodle.org/dev/Hook}
 *
 * Example of hook callback:
 *
 * $callbacks = array(
 *      array(
 *          'hookname' => '\core\hook\some_hook_name',
 *          'callback' => 'core_something\callbacks::some_hook_name',
 *          // 'includefile' => 'tag/lib.php', // optional, if the callback is not in the autoloaded location.
 *          'priority' => 0, // optional, callbacks with higher priority will be executed earlier.
 *          'component' => 'core_tag', // in plugins this attribute will be ignored and replaced with full plugin name
 *      ),
 * );
 *
 * @package   core
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Hooks that exist in Moodle core.
$hooks = array(
    '\core\hook\pre_block_instance_delete',  // Executed right before block instance is deleted.
    '\core\hook\pre_course_category_delete', // Executed right before course category is deleted.
    '\core\hook\pre_course_delete',          // Executed right before course is deleted.
    '\core\hook\pre_course_module_delete',   // Executed right before course module is deleted.
    '\core\hook\pre_user_delete',            // Executed right before user is deleted.
);

// Callbacks implemented by Moodle core.
$callbacks = array(

);

