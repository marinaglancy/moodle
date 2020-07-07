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
 * Unit tests for lib/outputcomponents.php.
 *
 * @package   core
 * @category  phpunit
 * @copyright 2011 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

use advanced_testcase;
use coding_exception;

/**
 * Unit tests for the `icon_system` class.
 */
class icon_system_test extends advanced_testcase {
    /**
     * Check whether the supplied classes are valid icon subsystems of the supplied one.
     *
     * @dataProvider is_valid_subsystem_provider
     */
    public function test_is_valid_subsystem(string $parent, string $system, bool $expected): void {
        $this->assertEquals($expected, $parent::is_valid_system($system));
    }

    public function is_valid_subsystem_provider(): array {
        return $this->icon_system_provider();
    }

    /**
     * @dataProvider invalid_instance_provider
     */
    public function test_invalid_instance(string $parent, string $system): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid icon system requested '{$system}'");

        $x = $parent::instance($system);
    }

    public function invalid_instance_provider(): array {
        return array_filter(
            $this->icon_system_provider(),
            function($data) {
                return !$data[2];
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * @dataProvider valid_instance_provider
     */
    public function test_valid_instance(string $parent, string $system): void {
        $instance = $parent::instance($system);
        $this->assertInstanceOf($parent, $instance);
    }

    public function valid_instance_provider(): array {
        return array_filter(
            $this->icon_system_provider(),
            function($data) {
                return $data[2];
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function test_instance_singleton(): void {
        $singleton = icon_system::instance();

        // Calling instance() again returns the same singleton.
        $this->assertSame($singleton, icon_system::instance());
    }

    public function test_instance_singleton_child(): void {
        global $PAGE;
        $singleton = icon_system::instance();

        $defaultsystem = $PAGE->theme->get_icon_system();
        $this->assertSame($singleton, icon_system::instance($defaultsystem));
    }

    /**
     * @dataProvider valid_instance_provider
     */
    public function test_instance_singleton_named(string $parent, string $child): void {
        $iconsystem = icon_system::instance($child);
        $this->assertInstanceOf($parent, $iconsystem);
    }

    /**
     * @dataProvider valid_instance_provider
     */
    public function test_instance_singleton_named_child(string $parent, string $child): void {
        $iconsystem = $parent::instance($child);
        $this->assertInstanceOf($parent, $iconsystem);
    }

    public function test_instance_singleton_reset(): void {
        $singleton = icon_system::instance();

        // Reset the cache
        icon_system::reset_caches();

        // Calling instance() again returns a new singleton.
        $newsingleton = icon_system::instance();
        $this->assertNotSame($singleton, $newsingleton);

        // Calling it again gets the new singleton.
        $this->assertSame($newsingleton, icon_system::instance());
    }

    /**
     * Returns data for data providers containing:
     * - parent icon system
     * - child icon system
     * - whether it is a valid child
     */
    public function icon_system_provider(): array {
        return [
            'icon_system => icon_system_standard' => [
                icon_system::class,
                icon_system_standard::class,
                true,
            ],
            'icon_system => icon_system_fontawesome' => [
                icon_system::class,
                icon_system_fontawesome::class,
                true,
            ],
            'icon_system => \theme_classic\output\icon_system_fontawesome' => [
                icon_system::class,
                \theme_classic\output\icon_system_fontawesome::class,
                true,
            ],
            'icon_system => notification' => [
                icon_system::class,
                notification::class,
                false,
            ],

            'icon_system_standard => icon_system_standard' => [
                icon_system_standard::class,
                icon_system_standard::class,
                true,
            ],
            'icon_system_standard => icon_system_fontawesome' => [
                icon_system_standard::class,
                icon_system_fontawesome::class,
                false,
            ],
            'icon_system_standard => \theme_classic\output\icon_system_fontawesome' => [
                icon_system_standard::class,
                \theme_classic\output\icon_system_fontawesome::class,
                false,
            ],
            'icon_system_fontawesome => icon_system_standard' => [
                icon_system_fontawesome::class,
                icon_system_standard::class,
                false,
            ],
        ];
    }
}
