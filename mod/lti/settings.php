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
 * This file defines the global lti administration form
 *
 * @package mod_lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @copyright  2015 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$modltifolder = new admin_category('modltifolder', new lang_string('pluginname', 'mod_lti'), $module->is_enabled() === false);
$ADMIN->add('modsettings', $modltifolder);

$settings->visiblename = new lang_string('settings', 'mod_lti');
$ADMIN->add('modltifolder', $settings);

$ADMIN->add('modltifolder', new admin_externalpage('ltitoolconfigure',
        get_string('manage_external_tools', 'lti'),
        new moodle_url('/mod/lti/toolconfigure.php')));

foreach (core_plugin_manager::instance()->get_plugins_of_type('ltisource') as $plugin) {
    /*
     * @var \mod_lti\plugininfo\ltisource $plugin
     */
    $plugin->load_settings($ADMIN, 'modltifolder', $hassiteconfig);
}

// Tell core we already added the settings structure.
$settings = null;

