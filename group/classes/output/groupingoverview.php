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
 *
 *
 * @package   core_group
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_group\output;

defined('MOODLE_INTERNAL') || die();

use renderer_base;
use context_course;
use moodle_url;
use html_writer;

/**
 *
 * @package   core_group
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groupingoverview implements \templatable {
    
    protected $grouping;
    protected $groups;

    /**
     * Constructor
     */
    public function __construct($grouping, $allgroups, $members) {
        $groupdata = $members[$grouping->id];
        $this->grouping = $grouping;
        $this->groups = array();
        foreach ($groupdata as $gpid => $users) {
            if (!array_key_exists($gpid, $allgroups)) {
                continue;
            }
            $this->groups[$gpid] = new groupoverview($allgroups[$gpid], $users);
        }
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $groups = array();
        foreach ($this->groups as $group) {
            $groups[] = $group->export_for_template($output);
        }
        $context = context_course::instance($this->grouping->courseid);
        $description = '';
        if ($this->grouping->id > 0 && strval($this->grouping->description) !== '') {
            $description = file_rewrite_pluginfile_urls($this->grouping->description, 'pluginfile.php',
                    $context->id, 'grouping', 'description', $this->grouping->id);
            $description = format_text($description, $this->grouping->descriptionformat,
                    array('overflowdiv' => true));
        }
        return array(
            'id' => $this->grouping->id,
            'idnumber' => $this->grouping->id, // TODO s()?
            'name' => format_string($this->grouping->name, true, array('context' => $context)),
            'description' => $description,
            'groups' => $groups,
            'groupscount' => count($groups)
        );
    }

    public function render(renderer_base $output) {
        return $output->render_from_template('core_group/groupingoverview', $this->export_for_template($output));
    }
}