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

namespace mod_data\local\hooks\navigation;

/**
 * Hook callbacks for mod_data
 *
 * @package    mod_data
 * @copyright  2023 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_administration_extend {

    /**
     * Extends site administration tree
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     */
    public static function callback(\core\hook\navigation\site_administration_extend $hook): void {
        global $CFG;

        $ADMIN = $hook->get_admin_root();
        $settings = $hook->create_settingpage_for_plugin(explode('\\', static::class)[0]);

        if ($settings && $ADMIN->fulltree) {
            if (empty($CFG->enablerssfeeds)) {
                $options = array(0 => get_string('rssglobaldisabled', 'admin'));
                $str = get_string('configenablerssfeeds', 'data') . '<br />' .
                    get_string('configenablerssfeedsdisabled2', 'admin');

            } else {
                $options = [0 => get_string('no'), 1 => get_string('yes')];
                $str = get_string('configenablerssfeeds', 'data');
            }
            $settings->add(new \admin_setting_configselect('data_enablerssfeeds', get_string('enablerssfeeds', 'admin'),
                               $str, 0, $options));
        }
    }
}
