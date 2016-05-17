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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file contains all the backup steps that will be used
 * by the backup_lti_activity_task
 *
 * @package mod_lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete assignment structure for backup, with file and id annotations
 */
class backup_lti_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        $config = get_config('lti');

        // Define each element separated.
        $lti = new backup_nested_element('lti', array('id'), array(
            'name',
            'intro',
            'introformat',
            'timecreated',
            'timemodified',
            'typeid',
            'toolurl',
            'securetoolurl',
            'preferheight',
            'launchcontainer',
            'instructorchoicesendname',
            'instructorchoicesendemailaddr',
            'instructorchoiceacceptgrades',
            'instructorchoiceallowroster',
            'instructorchoiceallowsetting',
            'grade',
            'instructorcustomparameters',
            'debuglaunch',
            'showtitlelaunch',
            'showdescriptionlaunch',
            'icon',
            'secureicon',
            )
        );

        if (!empty($config->backupsecret)) {
            $lti->add_final_elements(['resourcekey', 'password']);
        }

        $ltitype  = new backup_nested_element('ltitype', array('id'), array(
            'name',
            'baseurl',
            'tooldomain',
            'state',
            'course',
            'coursevisible',
            'toolproxyid',
            'enabledcapability',
            'parameter',
            'icon',
            'secureicon',
            'createdby',
            'timecreated',
            'timemodified',
            'description',
            'toolproxy_name',
            'toolproxy_regurl',
            'toolproxy_state',
            'toolproxy_guid',
            'toolproxy_secret',
            'toolproxy_vendorcode',
            'toolproxy_capabilityoffered',
            'toolproxy_serviceoffered',
            'toolproxy_toolproxy',
            'toolproxy_createdby',
            'toolproxy_timecreated',
            'toolproxy_timemodified',
            )
        );

        $ltitypesconfigs = new backup_nested_element('ltitypesconfigs');
        $ltitypesconfig  = new backup_nested_element('ltitypesconfig', array('id'), array(
            'name',
            'value',
            )
        );

        $ltitoolproxy = new backup_nested_element('ltitoolproxy', array('id'));

        $ltitoolsettings = new backup_nested_element('ltitoolsettings');
        $ltitoolsetting  = new backup_nested_element('ltitoolsetting', array('id'), array(
                'course',
                'coursemoduleid',
                'settings',
                'timecreated',
                'timemodified',
            )
        );

        $ltisubmissions = new backup_nested_element('ltisubmissions');
        $ltisubmission = new backup_nested_element('ltisubmission', array('id'), array(
            'userid',
            'datesubmitted',
            'dateupdated',
            'gradepercent',
            'originalgrade',
            'launchid',
            'state'
        ));

        // Build the tree
        $lti->add_child($ltitype);
        $ltitype->add_child($ltitypesconfigs);
        $ltitypesconfigs->add_child($ltitypesconfig);
        $ltitype->add_child($ltitoolproxy);
        $ltitoolproxy->add_child($ltitoolsettings);
        $ltitoolsettings->add_child($ltitoolsetting);
        $lti->add_child($ltisubmissions);
        $ltisubmissions->add_child($ltisubmission);

        // Define sources.
        $lti->set_source_table('lti', array('id' => backup::VAR_ACTIVITYID));

        $ltitypedata = $this->retrieve_lti_type();

        $ltitype->set_source_array($ltitypedata ? [$ltitypedata] : []);

        if (isset($ltitypedata->baseurl)) {
            // Add type config values only if the type was backed up.
            $sql = "SELECT lc.*
                FROM {lti_types_config} lc
                WHERE lc.typeid = ?";
            if (empty($config->backupsecret)) {
                $sql .= " AND lc.name != 'password' AND lc.name != 'resourcekey'";
            }
            $ltitypesconfig->set_source_sql($sql,
                [backup_helper::is_sqlparam($ltitypedata->id)]);
        }

        if (isset($ltitypedata->toolproxyid)) {
            $ltitoolproxy->set_source_array([['id' => $ltitypedata->toolproxyid]]);
        } else {
            $ltitoolproxy->set_source_array([]);
        }

        if (isset($ltitypedata->toolproxy_regurl)) {
            // If this is LTI 2 tool that was backed up, add settings both global and
            // for the current activity.
            $ltitoolsetting->set_source_sql("SELECT *
                FROM {lti_tool_settings}
                WHERE toolproxyid = ?
                AND ((course IS NULL AND coursemoduleid IS NULL) OR (course = ? AND coursemoduleid = ?))",
                [backup_helper::is_sqlparam($ltitypedata->toolproxyid), backup::VAR_COURSEID, backup::VAR_ACTIVITYID]);
        } else if (isset($ltitypedata->toolproxyid)) {
            // If this is LTI 2 tool that was not backed up, add only settings
            // for the current activity.
            $ltitoolsetting->set_source_sql("SELECT *
                FROM {lti_tool_settings}
                WHERE toolproxyid = ? AND course = ? AND coursemoduleid = ?",
                [backup_helper::is_sqlparam($ltitypedata->toolproxyid), backup::VAR_COURSEID, backup::VAR_ACTIVITYID]);
        }

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $ltisubmission->set_source_table('lti_submission', array('ltiid' => backup::VAR_ACTIVITYID));
        }

        // Define id annotations
        $ltitype->annotate_ids('user', 'createdby');
        $ltitype->annotate_ids('course', 'course');
        $ltitype->annotate_ids('user', 'toolproxy_createdby');
        $ltitoolsetting->annotate_ids('course', 'course');
        $ltitoolsetting->annotate_ids('course_modules', 'coursemoduleid');
        $ltisubmission->annotate_ids('user', 'userid');

        // Define file annotations.
        $lti->annotate_files('mod_lti', 'intro', null); // This file areas haven't itemid.

        // Add support for subplugin structures.
        $this->add_subplugin_structure('ltisource', $lti, true);
        $this->add_subplugin_structure('ltiservice', $lti, true);

        // Return the root element (lti), wrapped into standard activity structure.
        return $this->prepare_activity_structure($lti);
    }

    protected function retrieve_lti_type() {
        global $DB;
        $canconfigureglobal = (int)has_capability('moodle/site:config', context_system::instance());
        $sql = "SELECT lt.*,
                  lp.name AS toolproxy_name,
                  lp.regurl AS toolproxy_regurl,
                  lp.state AS toolproxy_state,
                  lp.guid AS toolproxy_guid,
                  lp.secret AS toolproxy_secret,
                  lp.vendorcode AS toolproxy_vendorcode,
                  lp.capabilityoffered AS toolproxy_capabilityoffered,
                  lp.serviceoffered AS toolproxy_serviceoffered,
                  lp.toolproxy AS toolproxy_toolproxy,
                  lp.createdby AS toolproxy_createdby,
                  lp.timecreated AS toolproxy_timecreated,
                  lp.timemodified AS toolproxy_timemodified
                FROM {lti} l
                JOIN {lti_types} lt ON lt.id = l.typeid
                LEFT JOIN {lti_tool_proxies} lp ON lp.id = lt.toolproxyid
                WHERE l.id = ?";
        $params = [$this->task->get_activityid()];

        $record = $DB->get_record_sql($sql, $params);
        if (!$canconfigureglobal && $record && $record->course == SITEID) {
            // User without permission to configure global LTI types can not
            // backup information about them. However we still back up the
            // ids of type and proxy so if restore is performed on the same
            // site we can match them to existing type/proxy.
            // Name is always backed up because it is visible for the teacher.
            $allowedkeys = ['id', 'name', 'toolproxyid'];
            foreach ($record as $key => $value) {
                if (!in_array($key, $allowedkeys)) {
                    $record->$key = null;
                }
            }
        }

        return $record;
    }
}
