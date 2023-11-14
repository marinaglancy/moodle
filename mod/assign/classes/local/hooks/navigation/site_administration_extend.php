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

namespace mod_assign\local\hooks\navigation;

use admin_category;
use admin_setting_configcheckbox;
use admin_setting_configduration;
use admin_setting_configempty;
use admin_setting_configselect;
use admin_setting_configtextarea;
use admin_setting_flag;
use admin_setting_heading;
use admin_settingpage;
use assign_admin_page_manage_assign_plugins;
use core_component;
use core_plugin_manager;
use lang_string;

/**
 * Hook callbacks for mod_assign
 *
 * @package    mod_assign
 * @copyright  2023 Marina Glancy
 * @author     2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_administration_extend {

    /**
     * Extends site administration tree
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     */
    public static function callback(\core\hook\navigation\site_administration_extend $hook): void {
        $ADMIN = $hook->get_admin_root();
        $pluginname = explode('\\', static::class)[0];
        $module = $plugininfo = core_plugin_manager::instance()->get_plugin_info($pluginname);
        $settingfolder = new admin_category('modassignfolder', new lang_string('pluginname', 'mod_assign'),
            $module->is_enabled() === false);

        $section = $plugininfo->get_settings_section_name();
        $settings = new admin_settingpage($section, new lang_string('settings', 'mod_assign'), 'moodle/site:config',
            $module->is_enabled() === false);

        if ($ADMIN->fulltree) {
            self::populate_assign_setting_page($hook, $settings);
        }
        $settingfolder->add('modassignfolder', $settings);

        self::add_assign_subplugins($hook, $settingfolder, $module->is_enabled());

        // For assignment plugin we do not want just a single page with settings, but the whole folder instead.
        $hook->set_custom_settingpage_for_plugin($pluginname, $settingfolder);
    }

    /**
     * Adds settings to the assignemnt settings page
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     * @param admin_settingpage $settings
     * @return void
     */
    protected static function populate_assign_setting_page(\core\hook\navigation\site_administration_extend $hook,
            admin_settingpage $settings) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/adminlib.php');

        $ADMIN = $hook->get_admin_root();

        $menu = array();
        foreach (core_component::get_plugin_list('assignfeedback') as $type => $notused) {
            $visible = !get_config('assignfeedback_' . $type, 'disabled');
            if ($visible) {
                $menu['assignfeedback_' . $type] = new lang_string('pluginname', 'assignfeedback_' . $type);
            }
        }

        // The default here is feedback_comments (if it exists).
        $name = new lang_string('feedbackplugin', 'mod_assign');
        $description = new lang_string('feedbackpluginforgradebook', 'mod_assign');
        $settings->add(new admin_setting_configselect('assign/feedback_plugin_for_gradebook',
                                                        $name,
                                                        $description,
                                                        'assignfeedback_comments',
                                                        $menu));

        $name = new lang_string('showrecentsubmissions', 'mod_assign');
        $description = new lang_string('configshowrecentsubmissions', 'mod_assign');
        $settings->add(new admin_setting_configcheckbox('assign/showrecentsubmissions',
                                                        $name,
                                                        $description,
                                                        0));

        $name = new lang_string('sendsubmissionreceipts', 'mod_assign');
        $description = new lang_string('sendsubmissionreceipts_help', 'mod_assign');
        $settings->add(new admin_setting_configcheckbox('assign/submissionreceipts',
                                                        $name,
                                                        $description,
                                                        1));

        $name = new lang_string('submissionstatement', 'mod_assign');
        $description = new lang_string('submissionstatement_help', 'mod_assign');
        $default = get_string('submissionstatementdefault', 'mod_assign');
        $setting = new admin_setting_configtextarea('assign/submissionstatement',
                                                        $name,
                                                        $description,
                                                        $default);
        $setting->set_force_ltr(false);
        $settings->add($setting);

        $name = new lang_string('submissionstatementteamsubmission', 'mod_assign');
        $description = new lang_string('submissionstatement_help', 'mod_assign');
        $default = get_string('submissionstatementteamsubmissiondefault', 'mod_assign');
        $setting = new admin_setting_configtextarea('assign/submissionstatementteamsubmission',
            $name,
            $description,
            $default);
        $setting->set_force_ltr(false);
        $settings->add($setting);

        $name = new lang_string('submissionstatementteamsubmissionallsubmit', 'mod_assign');
        $description = new lang_string('submissionstatement_help', 'mod_assign');
        $default = get_string('submissionstatementteamsubmissionallsubmitdefault', 'mod_assign');
        $setting = new admin_setting_configtextarea('assign/submissionstatementteamsubmissionallsubmit',
            $name,
            $description,
            $default);
        $setting->set_force_ltr(false);
        $settings->add($setting);

        $name = new lang_string('maxperpage', 'mod_assign');
        $options = array(
            -1 => get_string('unlimitedpages', 'mod_assign'),
            10 => 10,
            20 => 20,
            50 => 50,
            100 => 100,
        );
        $description = new lang_string('maxperpage_help', 'mod_assign');
        $settings->add(new admin_setting_configselect('assign/maxperpage',
                                                        $name,
                                                        $description,
                                                        -1,
                                                        $options));

        $name = new lang_string('defaultsettings', 'mod_assign');
        $description = new lang_string('defaultsettings_help', 'mod_assign');
        $settings->add(new admin_setting_heading('defaultsettings', $name, $description));

        $name = new lang_string('alwaysshowdescription', 'mod_assign');
        $description = new lang_string('alwaysshowdescription_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/alwaysshowdescription',
                                                        $name,
                                                        $description,
                                                        1);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('allowsubmissionsfromdate', 'mod_assign');
        $description = new lang_string('allowsubmissionsfromdate_help', 'mod_assign');
        $setting = new admin_setting_configduration('assign/allowsubmissionsfromdate',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('duedate', 'mod_assign');
        $description = new lang_string('duedate_help', 'mod_assign');
        $setting = new admin_setting_configduration('assign/duedate',
                                                        $name,
                                                        $description,
                                                        604800);
        $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('cutoffdate', 'mod_assign');
        $description = new lang_string('cutoffdate_help', 'mod_assign');
        $setting = new admin_setting_configduration('assign/cutoffdate',
                                                        $name,
                                                        $description,
                                                        1209600);
        $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('enabletimelimit', 'mod_assign');
        $description = new lang_string('enabletimelimit_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox(
            'assign/enabletimelimit',
            $name,
            $description,
            0
        );
        $settings->add($setting);

        $name = new lang_string('gradingduedate', 'mod_assign');
        $description = new lang_string('gradingduedate_help', 'mod_assign');
        $setting = new admin_setting_configduration('assign/gradingduedate',
                                                        $name,
                                                        $description,
                                                        1209600);
        $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('submissiondrafts', 'mod_assign');
        $description = new lang_string('submissiondrafts_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/submissiondrafts',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('requiresubmissionstatement', 'mod_assign');
        $description = new lang_string('requiresubmissionstatement_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/requiresubmissionstatement',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        // Constants from "locallib.php".
        $options = array(
            'none' => get_string('attemptreopenmethod_none', 'mod_assign'),
            'manual' => get_string('attemptreopenmethod_manual', 'mod_assign'),
            'untilpass' => get_string('attemptreopenmethod_untilpass', 'mod_assign')
        );
        $name = new lang_string('attemptreopenmethod', 'mod_assign');
        $description = new lang_string('attemptreopenmethod_help', 'mod_assign');
        $setting = new admin_setting_configselect('assign/attemptreopenmethod',
                                                        $name,
                                                        $description,
                                                        'none',
                                                        $options);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        // Constants from "locallib.php".
        $options = array(-1 => get_string('unlimitedattempts', 'mod_assign'));
        $options += array_combine(range(1, 30), range(1, 30));
        $name = new lang_string('maxattempts', 'mod_assign');
        $description = new lang_string('maxattempts_help', 'mod_assign');
        $setting = new admin_setting_configselect('assign/maxattempts',
                                                        $name,
                                                        $description,
                                                        -1,
                                                        $options);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('teamsubmission', 'mod_assign');
        $description = new lang_string('teamsubmission_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/teamsubmission',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('preventsubmissionnotingroup', 'mod_assign');
        $description = new lang_string('preventsubmissionnotingroup_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/preventsubmissionnotingroup',
            $name,
            $description,
            0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('requireallteammemberssubmit', 'mod_assign');
        $description = new lang_string('requireallteammemberssubmit_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/requireallteammemberssubmit',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('teamsubmissiongroupingid', 'mod_assign');
        $description = new lang_string('teamsubmissiongroupingid_help', 'mod_assign');
        $setting = new admin_setting_configempty('assign/teamsubmissiongroupingid',
                                                        $name,
                                                        $description);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('sendnotifications', 'mod_assign');
        $description = new lang_string('sendnotifications_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/sendnotifications',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('sendlatenotifications', 'mod_assign');
        $description = new lang_string('sendlatenotifications_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/sendlatenotifications',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('sendstudentnotificationsdefault', 'mod_assign');
        $description = new lang_string('sendstudentnotificationsdefault_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/sendstudentnotifications',
                                                        $name,
                                                        $description,
                                                        1);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('blindmarking', 'mod_assign');
        $description = new lang_string('blindmarking_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/blindmarking',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('hidegrader', 'mod_assign');
        $description = new lang_string('hidegrader_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/hidegrader',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('markingworkflow', 'mod_assign');
        $description = new lang_string('markingworkflow_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/markingworkflow',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('markingallocation', 'mod_assign');
        $description = new lang_string('markingallocation_help', 'mod_assign');
        $setting = new admin_setting_configcheckbox('assign/markingallocation',
                                                        $name,
                                                        $description,
                                                        0);
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);
    }

    /**
     * Adds sections to the assignemnt settings page for subplugins
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     * @param admin_category $settingfolder
     * @param bool $isenabled
     * @return void
     */
    protected static function add_assign_subplugins(\core\hook\navigation\site_administration_extend $hook,
            admin_category $settingfolder, bool $isenabled) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/adminlib.php');

        $submissionplugins = new admin_category('assignsubmissionplugins',
            new lang_string('submissionplugins', 'assign'), !$isenabled);
        $settingfolder->add('modassignfolder', $submissionplugins);

        $managesubmissions = new assign_admin_page_manage_assign_plugins('assignsubmission');
        $settingfolder->add('assignsubmissionplugins', $managesubmissions);

        $feedbackplugins = new admin_category('assignfeedbackplugins',
            new lang_string('feedbackplugins', 'assign'), !$isenabled);
        $settingfolder->add('modassignfolder', $feedbackplugins);

        $managefeedbacks = new assign_admin_page_manage_assign_plugins('assignfeedback');
        $settingfolder->add('assignfeedbackplugins', $managefeedbacks);

        $hook->add_plugintype_section('assignsubmission', 'assignsubmissionplugins');
        $hook->add_plugintype_section('assignfeedback', 'assignfeedbackplugins');
    }
}
