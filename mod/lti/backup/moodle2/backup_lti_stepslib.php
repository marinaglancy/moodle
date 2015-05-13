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

    /** @var stdClass stores config for 'lti' plugin */
    protected $config;

    protected function define_structure() {
        global $DB;

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

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
            'resourcekey_encrypted',
            'password_encrypted'
            )
        );

        $ltitypes = new backup_nested_element('ltitypes');
        $ltitype  = new backup_nested_element('ltitype', array('id'), array(
            'name',
            'baseurl',
            'tooldomain',
            'state',
            'coursevisible',
            'createdby',
            'timecreated',
            'timemodified',
            )
        );

        $ltitypesconfigs = new backup_nested_element('ltitypesconfigs');
        $ltitypesconfig  = new backup_nested_element('ltitypesconfig', array('id'), array(
            'typeid',
            'name',
            'value',
            )
        );

        // Build the tree
        $lti->add_child($ltitypes);
        $lti->add_child($ltitypesconfigs);
        $ltitypes->add_child($ltitype);
        $ltitypesconfigs->add_child($ltitypesconfig);

        // Define sources.
        $ltirecord = $DB->get_record('lti', ['id' => $this->task->get_activityid()]);
        // Allow to include encrypted consumer key and shared secret in the backup to be restored on the same site only.
        $this->encrypt_field($ltirecord, 'resourcekey');
        $this->encrypt_field($ltirecord, 'password');
        $lti->set_source_array([$ltirecord]);

        $ltitype->set_source_sql("SELECT lt.*
            FROM {lti} l
            JOIN {lti_types} lt ON lt.id = l.typeid
            WHERE l.id = ?", array(backup::VAR_ACTIVITYID));
        $ltitypesconfig->set_source_sql("SELECT lc.*
            FROM {lti} l
            JOIN {lti_types_config} lc ON lc.typeid = l.typeid
            WHERE lc.name != 'password'
            AND lc.name != 'resourcekey'
            AND lc.name != 'servicesalt'
            AND l.id = ?", array(backup::VAR_ACTIVITYID));

        // Define id annotations
        $ltitype->annotate_ids('user', 'createdby');

        // Define file annotations.
        $lti->annotate_files('mod_lti', 'intro', null); // This file areas haven't itemid.

        // Add support for subplugin structures.
        $this->add_subplugin_structure('ltisource', $lti, true);
        $this->add_subplugin_structure('ltiservice', $lti, true);

        // Return the root element (lti), wrapped into standard activity structure.
        return $this->prepare_activity_structure($lti);
    }

    /**
     * Allow to include encrypted secret in the backup to be restored on the same site only.
     *
     * Generate key and IV for openssl encryption if not previously generated and store then in the plugin config.
     * This will allow to restore on the same site but at the same time will not store the secret information
     * (similar to password) in the backup file when it can be shared.
     *
     * @see restore_lti_activity_structure_step::decrypt_field()
     * @param stdClass $record
     * @param string $fieldname field that need to be encrypted, for example if $record->secret was encrypted, after
     *    calling this function $record->secret will not exist but $record->secret_encrypted will be added
     */
    protected function encrypt_field($record, $fieldname) {
        if (!empty($record->$fieldname) && function_exists('openssl_encrypt')) {
            if ($this->config === null) {
                $this->config = get_config('lti') ?: new stdClass();
            }

            if (empty($this->config->backupencryptkey)) {
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                $key = openssl_random_pseudo_bytes(16);
                $this->config->backupencryptkey = base64_encode($key);
                $this->config->backupencryptiv = base64_encode($iv);
                set_config('backupencryptkey', $this->config->backupencryptkey, 'lti');
                set_config('backupencryptiv', $this->config->backupencryptiv, 'lti');
            } else {
                $iv = base64_decode($this->config->backupencryptiv);
                $key = base64_decode($this->config->backupencryptkey);
            }

            $record->{$fieldname . '_encrypted'} = openssl_encrypt($record->$fieldname, 'aes-256-cbc', $key, false, $iv);
        }
        // Always exclude the unencrypted field from the record even encryption was not possible.
        unset($record->$fieldname);
    }
}
