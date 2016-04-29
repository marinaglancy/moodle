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
 * This file contains all the restore steps that will be used
 * by the restore_lti_activity_task
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
 * Structure step to restore one lti activity
 */
class restore_lti_activity_structure_step extends restore_activity_structure_step {

    /** @var bool */
    protected $newltitype = false;

    protected function define_structure() {

        $paths = array();
        $lti = new restore_path_element('lti', '/activity/lti');
        $paths[] = new restore_path_element('ltitype', '/activity/lti/ltitype');
        $paths[] = new restore_path_element('ltitypesconfig', '/activity/lti/ltitype/ltitypesconfigs/ltitypesconfig');
        $paths[] = new restore_path_element('ltitoolproxy', '/activity/lti/ltitype/ltitoolproxy');
        $paths[] = new restore_path_element('ltitoolsetting', '/activity/lti/ltitype/ltitoolproxy/ltitoolsettings/ltitoolsetting');
        $paths[] = $lti;

        // Add support for subplugin structures.
        $this->add_subplugin_structure('ltisource', $lti);
        $this->add_subplugin_structure('ltiservice', $lti);

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_lti($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->servicesalt = uniqid('', true);

         // Grade used to be a float (whole numbers only), restore as int.
        $data->grade = (int) $data->grade;

        $data->typeid = 0;

        $newitemid = $DB->insert_record('lti', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process an lti type restore
     * @param mixed $data The data from backup XML file
     * @return void
     */
    protected function process_ltitype($data) {
        global $DB, $USER;

        $data = (object)$data;
        $oldid = $data->id;
        $oldproxyid = $data->toolproxyid;
        $data->createdby = $this->get_mappingid('user', $data->createdby) ?: $USER->id;

        $courseid = $this->get_courseid();
        $data->course = ($this->get_mappingid('course', $data->course) == $courseid) ? $courseid : SITEID;

        // Try to find existing lti type with the same properties.
        list($ltitypeid, $ltiproxyid) = $this->find_existing_lti_type($data);

        $this->newltitype = false;
        if (!$ltitypeid) {
            if ($data->course == SITEID && !has_capability('moodle/site:config', context_system::instance())) {
                // Non-admins restore as course tool even if it was system tool.
                $data->course = $courseid;
                if ($data->toolproxyid) {
                    // It is impossible to restore a LTI2 type into a course and current user is not allowed to restore in system.
                    $DB->update_record('lti', ['id' => $this->get_new_parentid('lti'), 'typeid' => null]);
                    $this->set_mapping('ltitype', $oldid, null);
                    $this->set_mapping('ltitoolproxy', $oldproxyid, null);
                    return;
                }
            }
            if ($data->toolproxyid) {
                $proxydata = new stdClass();
                foreach ($data as $key => $value) {
                    if ($key === 'proxytool_createdby') {
                        $proxydata->createdby = $this->get_mappingid('user', $value) ?: $data->createdby;
                    } else if (preg_match('/^proxytool_(.+)$/', $key, $matches)) {
                        $proxydata->{$matches[1]} = $value;
                    }
                }
                $data->toolproxyid = $ltiproxyid = $DB->insert_record('lti_tool_proxies', $proxydata);
            } else {
                $ltiproxyid = null;
            }
            $ltitypeid = $DB->insert_record('lti_types', $data);
            $this->newltitype = true;
        }

        // Add the typeid entry back to LTI module.
        $DB->update_record('lti', ['id' => $this->get_new_parentid('lti'), 'typeid' => $ltitypeid]);

        $this->set_mapping('ltitype', $oldid, $ltitypeid);
        if ($oldproxyid) {
            $this->set_mapping('ltitoolproxy', $oldproxyid, $ltiproxyid);
        }
    }

    /**
     * Attempts to find existing records in lti_type and lti_tool_proxies so we don't need to duplicate them
     * @param stdClass $data
     * @return int
     */
    protected function find_existing_lti_type($data) {
        global $DB;
        if ($ltitypeid = $this->get_mappingid('ltitype', $data->id)) {
            $toolproxyid = $data->toolproxyid ? $this->get_mappingid('ltitoolproxy', $data->toolproxyid) : null;
            return [$ltitypeid, $toolproxyid];
        }

        $ltitype = null;
        $params = (array)$data;
        if ($this->task->is_samesite()) {
            $sql = 'id = :id AND course = :course';
            $sql .= ($data->toolproxyid) ? ' AND toolproxyid = :toolproxyid' : ' AND toolproxyid IS NULL';
            // If we are restoring on the same site first try to find lti type with the same id.
            if ($ltitype = $DB->get_record_select('lti_types', $sql, $params, 'id, toolproxyid')) {
                return [$ltitype->id, $ltitype->toolproxyid];
            }
        }
        // Now try to find the same type on the current site available in this course.
        $sql = 'lt.baseurl = :baseurl AND lt.course = :course AND lt.tooldomain = :tooldomain AND lt.name = :name';

        // Search on the same level (site or course):
        $sqlsamelevel = 'SELECT lt.id, lt.toolproxyid
        FROM {lti_types} lt
        LEFT JOIN {lti_tool_proxies} lp ON lp.id = lt.toolproxyid
        WHERE ' . $sql;
        if ($data->toolproxyid) {
            $sqlsamelevel .= ' AND lp.id IS NOT NULL AND lp.regurl = :toolproxy_regurl';
        } else {
            $sqlsamelevel .= ' AND lt.toolproxyid IS NULL ';
        }
        $ltitype = $DB->get_record_sql($sqlsamelevel, $params);

        // If this was a site preconfigured tool search inside the course. LTI2 is not possible in the course.
        if (!$ltitype && $params['course'] == SITEID && !$params['toolproxyid']) {
            $params['course'] = $this->get_courseid();
            $ltitype = $DB->get_record_sql('SELECT lt.id, lt.toolproxyid FROM {lti_types} lt WHERE ' . $sql . ' AND lt.toolproxyid IS NULL', $params);
        }
        return $ltitype ? [$ltitype->id, $ltitype->toolproxyid] : [null, null];
    }

    /**
     * Process an lti config restore
     * @param mixed $data The data from backup XML file
     */
    protected function process_ltitypesconfig($data) {
        global $DB;

        $data = (object)$data;
        $data->typeid = $this->get_new_parentid('ltitype');

        // Only add configuration if the new lti_type was created.
        if ($data->typeid && $this->newltitype) {
            if ($data->name == 'servicesalt') {
                $data->value = uniqid('', true);
            }
            $DB->insert_record('lti_types_config', $data);
        }
    }

    /**
     * Process a restore of LTI tool registration
     * This method is empty because we actually process registration as part of process_ltitype()
     * @param mixed $data The data from backup XML file
     */
    protected function process_ltitoolproxy($data) {

    }

    /**
     * Process an lti tool registration settings restore
     * @param mixed $data The data from backup XML file
     */
    protected function process_ltitoolsetting($data) {
        global $DB;

        $data = (object)$data;
        $data->toolproxyid = $this->get_new_parentid('ltitoolproxy');

        if (!$data->toolproxyid) {
            return;
        }

        if ($data->coursemoduleid) {
            $data->course = $this->get_courseid();
            $data->coursemoduleid = $this->task->get_activityid();
        } else {
            if (!$this->newltitype) {
                return;
            }
            $data->course = null;
            $data->coursemoduleid = null;
        }

        $DB->insert_record('lti_tool_settings', $data);
    }

    protected function after_execute() {
        // Add lti related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_lti', 'intro', null);
    }
}
