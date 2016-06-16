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
 * Contains class core_group\output\groupoverview
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
use core_collator;

/**
 * Stores and prepares for output the information about groups and groupings in a course
 *
 * @package   core_group
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groupoverview implements \templatable {

    /** @var int */
    protected $courseid;
    /** @var stdClass[] */
    protected $allgroups;
    /** @var stdClass[] */
    protected $allgroupings;
    /** @var array */
    protected $allmembers;
    protected $notingroupsmembers;

    /**
     * Constructor
     */
    public function __construct($courseid) {
        global $DB;
        $this->courseid = $courseid;
        $this->allgroups = $DB->get_records('groups', array('courseid' => $courseid), 'name');
        $this->allgroupings = $DB->get_records('groupings', array('courseid' => $courseid), 'name');
        $this->allmembers = group_get_groups_members_for_overview($courseid);
        $this->notingroupsmembers = groups_get_users_without_groups($courseid);
        $context = context_course::instance($courseid);

        foreach ($this->allgroups as $gpid => $group) {
            $this->allgroups[$gpid]->formattedname = format_string($group->name, true,
                    array('context' => $context));
            $this->allgroups[$gpid]->picture = print_group_picture($group, $courseid, false, true, false);
            $description = file_rewrite_pluginfile_urls($group->description,
                    'pluginfile.php', $context->id, 'group', 'description', $gpid);
            $options = array('noclean' => true, 'overflowdiv' => true);
            $this->allgroups[$gpid]->formatteddescription = format_text($description,
                    $group->descriptionformat, $options);
        }
        core_collator::asort_objects_by_property($this->allgroups, 'formattedname');

        foreach ($this->allgroupings as $gpgid => $grouping) {
            $this->allgroupings[$gpgid]->formattedname = format_string($grouping->name, true,
                    array('context' => $context));
            $description = file_rewrite_pluginfile_urls($grouping->description,
                    'pluginfile.php', $context->id, 'grouping', 'description', $gpgid);
            $this->allgroupings[$gpgid]->formatteddescription = format_text($description, $grouping->descriptionformat,
                    array('overflowdiv' => true));
        }
        core_collator::asort_objects_by_property($this->allgroupings, 'formattedname', core_collator::SORT_NATURAL);

        if (!empty($this->allmembers[OVERVIEW_GROUPING_GROUP_NO_GROUPING])) {
            $this->allgroupings[OVERVIEW_GROUPING_GROUP_NO_GROUPING] = (object)array(
                'id' => OVERVIEW_GROUPING_GROUP_NO_GROUPING,
                'idnumber' => '',
                'formattedname' => get_string('notingrouping', 'group'),
                'formatteddescription' => '',
            );
        }

        if (!empty($this->notingroupsmembers)) {
            $this->allgroups[OVERVIEW_NO_GROUP] = (object)array(
                'id' => OVERVIEW_NO_GROUP,
                'idnumber' => '',
                'formattedname' => get_string('nogroup', 'group'),
                'formatteddescription' => '',
                'picture' => '',
            );
        }
    }

    protected function get_members($userslist) {
        $members = array();
        foreach ($userslist as $user) {
            $url = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $this->courseid));
            $members[] = html_writer::link($url, fullname($user, true)); // TODO capabilities!
        }
        return $members;
    }

    protected function get_grouping_actions($grouping, renderer_base $output) {
        if ($grouping->id <= 0) {
            return '';
        }
        $context = context_course::instance($this->courseid);
        $stredit         = get_string('edit');
        $canchangeidnumber = has_capability('moodle/course:changeidnumber', $context);
        $strdelete       = get_string('delete');
        $strmanagegrping = get_string('showgroupsingrouping', 'group');

        $url = new moodle_url('/group/grouping.php', array('id' => $grouping->id));
        $buttons  = html_writer::link($url, $output->pix_icon('t/edit', $stredit, 'core',
            array('class' => 'iconsmall')), array('title' => $stredit));
        if (empty($grouping->idnumber) || $canchangeidnumber) {
            // It's only possible to delete groups without an idnumber unless the user has the changeidnumber capability.
            $url = new moodle_url('/group/grouping.php', array('id' => $grouping->id, 'delete' => 1));
            $buttons .= html_writer::link($url, $output->pix_icon('t/delete', $strdelete, 'core',
                array('class' => 'iconsmall')), array('title' => $strdelete));
        } else {
            $buttons .= $output->spacer();
        }
        $url = new moodle_url('/group/assign.php', array('id' => $grouping->id));
        $buttons .= html_writer::link($url, $output->pix_icon('t/groups', $strmanagegrping, 'core',
            array('class' => 'iconsmall')), array('title' => $strmanagegrping));

        return $buttons;

    }

    protected function get_group_actions($group, renderer_base $output) {
        if ($group->id <= 0) {
            return '';
        }
        $context = context_course::instance($this->courseid);
        $stredit         = get_string('editgroupsettings', 'group');
        $strdelete       = get_string('delete');
        $strusers        = get_string('adduserstogroup', 'group');

        $url = new moodle_url('/group/group.php', array('courseid' => $this->courseid, 'id' => $group->id));
        $buttons  = html_writer::link($url, $output->pix_icon('t/edit', $stredit, 'core',
            array('class' => 'iconsmall')), array('title' => $stredit));

        if (empty($group->idnumber) || has_capability('moodle/course:changeidnumber', $context)) {
            $url = new moodle_url('/group/delete.php', array('courseid' => $this->courseid, 'groups' => $group->id));
            $buttons .= html_writer::link($url, $output->pix_icon('t/delete', $strdelete, 'core',
                array('class' => 'iconsmall')), array('title' => $strdelete));
        }

        $url = new moodle_url('/group/members.php', array('group' => $group->id));
        $buttons .= html_writer::link($url, $output->pix_icon('t/groups', $strusers, 'core',
            array('class' => 'iconsmall')), array('title' => $strusers));



        return $buttons;
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
        foreach ($this->allgroupings as $grouping) {
            if ($grouping->id < 0 && empty($this->allmembers[$grouping->id])) {
                continue;
            }
            $groups = array();
            if (!empty($this->allmembers[$grouping->id])) {
                foreach ($this->allmembers[$grouping->id] as $gid => $groupmembers) {
                    $members = $this->get_members($groupmembers);
                    $group = $this->allgroups[$gid];
                    $groups[] = array(
                        'id' => $group->id,
                        'idnumber' => $group->idnumber,
                        'name' => $group->formattedname, // TODO
                        'description' => $group->formatteddescription, // TODO
                        'picture' => $group->picture,
                        'members' => join(', ', $members),
                        'memberscount' => count($members),
                        'actions' => $this->get_group_actions($group, $output),
                    );
                }
                \core_collator::asort_array_of_arrays_by_key($groups, 'name', core_collator::SORT_NATURAL);
            }
            $groupings[] = array(
                'id' => $grouping->id,
                'idnumber' => $grouping->idnumber,
                'name' => $grouping->formattedname, // TODO
                'description' => $grouping->formatteddescription, // TODO
                'groups' => array_values($groups),
                'groupscount' => count($groups),
                'actions' => $this->get_grouping_actions($grouping, $output),
            );
        }
        $groups = array();
        foreach ($this->allgroups as $group) {
            $groups[] = array(
                'id' => $group->id,
                'name' => $group->formattedname,
            );
        }
        \core_collator::asort_array_of_arrays_by_key($groups, 'name', core_collator::SORT_NATURAL);
        $members = $this->get_members($this->notingroupsmembers);
        return array(
            'groups' => array_values($groups),
            'groupings' => array_values($groupings),
            'notingroupsusers' => join(', ', $members),
            'notingroupsuserscount' => count($members),
            'creategroupingurl' => new moodle_url('/group/grouping.php', ['courseid' => $this->courseid]),
            'creategroupurl' => new moodle_url('/group/group.php', ['courseid' => $this->courseid]),
            'autocreategroupsurl' => new moodle_url('/group/autogroup.php', ['courseid' => $this->courseid]),
            'importgroupsurl' => new moodle_url('/group/import.php', ['id' => $this->courseid]),
        );
    }

    public function render(renderer_base $output) {
        global $PAGE;
        $hoverevents = array();
        foreach ($this->allgroups as $group) {
            $hoverevents[$group->id] = $group->formatteddescription;
        }
        if (count($hoverevents)>0) {
            $PAGE->requires->string_for_js('description', 'moodle');
            $PAGE->requires->js_init_call('M.core_group.init_hover_events', array($hoverevents));
        }
        echo $output->render_from_template('core_group/groupsoverview', $this->export_for_template($output));
    }
}