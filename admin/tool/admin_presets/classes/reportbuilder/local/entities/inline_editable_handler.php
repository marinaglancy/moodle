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

namespace tool_admin_presets\reportbuilder\local\entities;

/**
 * Class inline_editable_handler
 *
 * @package    tool_admin_presets
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inline_editable_handler implements \some_core_interface_for_handlers {
    /**
     * Implements method from \some_core_interface_for_handlers
     *
     * @param int $itemid
     * @param mixed $newvalue
     * @return ?\core\output\inplace_editable
     */
    public static function handle_update(int $itemid, $newvalue): ?\core\output\inplace_editable {
        // Handler for component='tool_admin_presets', itemtype='presetname'.
        global $DB;
        $context = \context_system::instance();
        \core_external\external_api::validate_context($context);

        require_capability('moodle/site:config', $context);

        $newvalue = clean_param($newvalue, PARAM_TEXT);
        $edithint = get_string('editadminpresetname', 'tool_admin_presets');
        $displayvalue = format_string($newvalue, true, ['context' => \context_system::instance(), 'escape' => false]);
        $editlabel = get_string('newvaluefor', 'form', $displayvalue);

        // Update value in database.
        $DB->set_field('adminpresets', 'name', $newvalue, [
            'id' => $itemid,
            'iscore' => \core_adminpresets\manager::NONCORE_PRESET,
        ]);
    }
}
