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

namespace tool_admin_presets\local\hooks\navigation;

use admin_externalpage;
use moodle_url;

/**
 * Hook callbacks for tool_admin_presets
 *
 * @package    tool_admin_presets
 * @copyright  2023 Marina Glancy
 * @author     2021 Pimenko <support@pimenko.com><pimenko.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_administration_extend {

    /**
     * Extend Site administration tree
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     */
    public static function callback(\core\hook\navigation\site_administration_extend $hook): void {
        $admin = $hook->get_admin_root();

        if ($hook->has_site_config()) {
            $admin->add('root', new admin_externalpage('tool_admin_presets',
                get_string('pluginname', 'tool_admin_presets'),
                new moodle_url('/admin/tool/admin_presets/index.php')));
        }
    }
}
