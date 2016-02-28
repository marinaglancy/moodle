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
class groupoverview implements \templatable {

    protected $group;
    protected $groupmembers;

    /**
     * Constructor
     */
    public function __construct($group, $groupmembers) {
        $this->group = $group;
        $this->groupmembers = $groupmembers;
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
        $context = context_course::instance($this->group->courseid);
        $description = $picture = '';
        $members = array();
        foreach ($this->groupmembers as $user) {
            $url = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $this->group->courseid));
            $members[] = html_writer::link($url, fullname($user, true)); // TODO capabilities!
        }
        return array(
            'id' => $this->group->id,
            'idnumber' => isset($this->group->idnumber) ? $this->group->idnumber : '', // TODO s()?
            'name' => $this->group->formattedname,
            'picture' => $this->group->picture,
            'description' => $this->group->formatteddescription,
            'members' => implode(', ', $members),
            'memberscount' => count($members),
        );
    }

    public function render(renderer_base $output) {
        return $output->render_from_template('core_group/groupverview', $this->export_for_template($output));
    }
}