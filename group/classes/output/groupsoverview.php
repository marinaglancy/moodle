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
class groupsoverview implements \templatable {

    protected $courseid;
    protected $allgroups;
    protected $allgroupings;
    protected $allmembers;

    /**
     * Constructor
     */
    public function __construct($courseid, $allgroups, $allgroupings, $allmembers) {
        $this->courseid = $courseid;
        $this->allgroups = $allgroups;
        $this->allgroupings = $allgroupings;
        $this->allmembers = $allmembers;
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
        $groupings = array();
        $notingroupsmembers = array();
        foreach ($this->allgroupings as $grouping) {
            if ($grouping->id < 0 && empty($this->allmembers[$grouping->id])) {
                continue;
            }
            $groups = array();
            if (!empty($this->allmembers[$grouping->id])) {
                foreach ($this->allmembers[$grouping->id] as $gid => $groupmembers) {
                    $members = array();
                    foreach ($groupmembers as $user) {
                        $url = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $this->courseid));
                        $members[] = html_writer::link($url, fullname($user, true)); // TODO capabilities!
                    }
                    if ($grouping->id == OVERVIEW_GROUPING_NO_GROUP) {
                        $notingroupsmembers = $members; // TODO bit stupid
                        continue 2;
                    }
                    $group = $this->allgroups[$gid];
                    $groups[] = array(
                        'id' => $group->id,
                        'idnumber' => $group->idnumber,
                        'name' => $group->formattedname, // TODO
                        'description' => $group->formatteddescription, // TODO
                        'members' => join(', ', $members),
                        'memberscount' => count($members)
                    );
                }
            }
            $groupings[] = array(
                'id' => $grouping->id,
                'idnumber' => '',//$grouping->idnumber,
                'name' => $grouping->formattedname, // TODO
                'description' => $grouping->formatteddescription, // TODO
                'groups' => $groups,
                'groupscount' => count($groups)
            );
        }
        return array(
            'groupings' => $groupings,
            'notingroupsmembers' => join(', ', $notingroupsmembers),
            'notingroupsmemberscount' => count($notingroupsmembers)
        );
    }

    public function render(renderer_base $output) {
        return $output->render_from_template('core_group/groupsoverview', $this->export_for_template($output));
    }
}