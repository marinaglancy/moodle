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
 * Print an overview of groupings & group membership
 *
 * @copyright  Matt Clarkson mattc@catalyst.net.nz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    core_group
 */

require_once('../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/group/lib.php');

define('OVERVIEW_NO_GROUP', -1); // The fake group for users not in a group.
define('OVERVIEW_GROUPING_GROUP_NO_GROUPING', -1); // The fake grouping for groups that have no grouping.
define('OVERVIEW_GROUPING_NO_GROUP', -2); // The fake grouping for users with no group.

$courseid   = required_param('id', PARAM_INT);
$groupid    = optional_param('group', 0, PARAM_INT);
$groupingid = optional_param('grouping', 0, PARAM_INT);

$returnurl = $CFG->wwwroot.'/group/index.php?id='.$courseid;
$rooturl   = $CFG->wwwroot.'/group/overview.php?id='.$courseid;

if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('invalidcourse');
}

$url = new moodle_url('/group/overview.php', array('id'=>$courseid));
if ($groupid !== 0) {
    $url->param('group', $groupid);
}
if ($groupingid !== 0) {
    $url->param('grouping', $groupingid);
}
$PAGE->set_url($url);

// Make sure that the user has permissions to manage groups.
require_login($course);

$context = context_course::instance($courseid);
require_capability('moodle/course:managegroups', $context);

$strgroups           = get_string('groups');
$stroverview         = get_string('overview', 'group');
$strgrouping         = get_string('grouping', 'group');
$strgroup            = get_string('group', 'group');
$strnotingrouping    = get_string('notingrouping', 'group');
$strfiltergroups     = get_string('filtergroups', 'group');
$strdescription      = get_string('description');
$strnotingroup       = get_string('notingrouplist', 'group');
$strnogroup          = get_string('nogroup', 'group');
$strnogrouping       = get_string('nogrouping', 'group');

$groups = group_get_groups_list_for_overview($courseid);
$groupings = group_get_groupings_list_for_overview($courseid);
$members = group_get_groups_members_for_overview($courseid, $groupings);

navigation_node::override_active_url(new moodle_url('/group/index.php', array('id'=>$courseid)));
$PAGE->navbar->add(get_string('overview', 'group'));

/// Print header
$PAGE->set_title($strgroups);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');
echo $OUTPUT->header();

// Add tabs
$currenttab = 'overview';
require('tabs.php');

/// Print overview
echo $OUTPUT->heading(format_string($course->shortname, true, array('context' => $context)) .' '.$stroverview, 3);

echo $strfiltergroups;

$options = array();
foreach ($groupings as $grouping) {
    $options[$grouping->id] = $grouping->formattedname;
}
unset($options[OVERVIEW_GROUPING_NO_GROUP]);
$popupurl = new moodle_url($rooturl.'&group='.$groupid);
$select = new single_select($popupurl, 'grouping', $options, $groupingid, array(0 => get_string('all')));
$select->label = $strgrouping;
$select->formid = 'selectgrouping';
echo $OUTPUT->render($select);

$options = array();
foreach ($groups as $group) {
    $options[$group->id] = $group->formattedname;
}
$popupurl = new moodle_url($rooturl.'&grouping='.$groupingid);
$select = new single_select($popupurl, 'group', $options, $groupid, array(0 => get_string('all')));
$select->label = $strgroup;
$select->formid = 'selectgroup';
echo $OUTPUT->render($select);

$tmpl = new \core_group\output\groupsoverview($courseid, $groups, $groupings, $members);
echo $tmpl->render($OUTPUT);

/*

/// Print table
$printed = false;
$hoverevents = array();
foreach ($members as $gpgid=>$groupdata) {
    if ($groupingid and $groupingid != $gpgid) {
        if ($groupingid > 0 || $gpgid > 0) { // Still show 'not in group' when 'no grouping' selected.
            continue; // Do not show.
        }
    }
    $table = new html_table();
    $table->head  = array(get_string('groupscount', 'group', count($groupdata)), get_string('groupmembers', 'group'), get_string('usercount', 'group'));
    $table->size  = array('20%', '70%', '10%');
    $table->align = array('left', 'left', 'center');
    $table->width = '90%';
    $table->data  = array();
    foreach ($groupdata as $gpid=>$users) {
        if ($groupid and $groupid != $gpid) {
            continue;
        }
        $line = array();
        $name = $groups[$gpid]->picture . $groups[$gpid]->formattedname;
        $jsdescription = $groups[$gpid]->formatteddescription;
        if (empty($jsdescription)) {
            $line[] = $name;
        } else {
            $line[] = html_writer::tag('span', $name, array('class' => 'group_hoverdescription', 'data-groupid' => $gpid));
            $hoverevents[$gpid] = $jsdescription;
        }
        $fullnames = array();
        foreach ($users as $user) {
            $fullnames[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.fullname($user, true).'</a>';
        }
        $line[] = implode(', ', $fullnames);
        $line[] = count($users);
        $table->data[] = $line;
    }
    if ($groupid and empty($table->data)) {
        continue;
    }
    if ($gpgid < 0) {
        // Display 'not in group' for grouping id == OVERVIEW_GROUPING_NO_GROUP.
        if ($gpgid == OVERVIEW_GROUPING_NO_GROUP) {
            echo $OUTPUT->heading($strnotingroup, 3);
        } else {
            echo $OUTPUT->heading($strnotingrouping, 3);
        }
    } else {
        echo $OUTPUT->heading($groupings[$gpgid]->formattedname, 3);
        echo $OUTPUT->box($groupings[$gpgid]->formatteddescription, 'generalbox boxwidthnarrow boxaligncenter');
    }
    echo html_writer::table($table);
    $printed = true;
}

if (count($hoverevents)>0) {
    $PAGE->requires->string_for_js('description', 'moodle');
    $PAGE->requires->js_init_call('M.core_group.init_hover_events', array($hoverevents));
}
*/
echo $OUTPUT->footer();
