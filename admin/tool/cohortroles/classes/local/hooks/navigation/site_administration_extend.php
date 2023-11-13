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

namespace tool_cohortroles\local\hooks\navigation;

use admin_externalpage;
use context_system;
use moodle_url;

/**
 * Hook callbacks for tool_cohortroles
 *
 * @package    tool_cohortroles
 * @copyright  2023 Marina Glancy
 * @author     2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_administration_extend {

    /**
     * Extend Site administration tree
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     */
    public static function callback(\core\hook\navigation\site_administration_extend $hook): void {
        $adminroot = $hook->get_admin_root();

        // This tool's required capabilities.
        $capabilities = [
            'moodle/cohort:view',
            'moodle/role:manage'
        ];

        // Check if the user has all of the required capabilities.
        $context = context_system::instance();
        $hasaccess = has_all_capabilities($capabilities, $context);

        // Add this admin page only if the user has all of the required capabilities.
        if ($hasaccess) {
            $str = get_string('managecohortroles', 'tool_cohortroles');
            $url = new moodle_url('/admin/tool/cohortroles/index.php');
            $adminroot->add('roles', new admin_externalpage('toolcohortroles', $str, $url, $capabilities));
        }
    }
}
