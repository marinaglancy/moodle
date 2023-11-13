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

namespace block_course_list\local\hooks\navigation;

use admin_setting_configcheckbox;
use admin_setting_configselect;

/**
 * Hook callbacks for block_course_list
 *
 * @package    block_course_list
 * @copyright  2023 Marina Glancy
 * @author     2007 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_administration_extend {

    /**
     * Extends site administration tree
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     */
    public static function callback(\core\hook\navigation\site_administration_extend $hook): void {
        $ADMIN = $hook->get_admin_root();
        $settings = $hook->create_settingpage_for_plugin(explode('\\', static::class)[0]);

        if ($ADMIN->fulltree) {
            $options = [
                'all' => get_string('allcourses', 'block_course_list'),
                'own' => get_string('owncourses', 'block_course_list'),
            ];

            $settings->add(new admin_setting_configselect('block_course_list_adminview',
                                get_string('adminview', 'block_course_list'),
                                get_string('configadminview', 'block_course_list'), 'all', $options));

            $settings->add(new admin_setting_configcheckbox('block_course_list_hideallcourseslink',
                                get_string('hideallcourseslink', 'block_course_list'),
                                get_string('confighideallcourseslink', 'block_course_list'), 0));
        }
    }
}
