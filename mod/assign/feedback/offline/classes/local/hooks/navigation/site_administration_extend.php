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

namespace assignfeedback_offline\local\hooks\navigation;

use admin_setting_configcheckbox;
use lang_string;

/**
 * Hook callbacks for assignfeedback_offline
 *
 * @package    assignfeedback_offline
 * @copyright  2023 Marina Glancy
 * @author     2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_administration_extend {

    /**
     * Extends site administration tree
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     */
    public static function callback(\core\hook\navigation\site_administration_extend $hook): void {
        $admin = $hook->get_admin_root();

        $settings = $hook->create_settingpage_for_plugin(explode('\\', static::class)[0]);
        if ($settings && $admin->fulltree) {
            $settings->add(new admin_setting_configcheckbox('assignfeedback_offline/default',
                    new lang_string('default', 'assignfeedback_offline'),
                    new lang_string('default_help', 'assignfeedback_offline'), 0));
        }

    }
}
