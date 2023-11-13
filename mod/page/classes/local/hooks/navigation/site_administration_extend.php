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

namespace mod_page\local\hooks\navigation;

use admin_setting_configcheckbox;
use admin_setting_configmultiselect;
use admin_setting_configselect;
use admin_setting_configtext;
use admin_setting_heading;

/**
 * Hook callbacks for mod_page
 *
 * @package    mod_page
 * @copyright  2023 Marina Glancy
 * @autor      2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_administration_extend {

    /**
     * Extends site administration tree
     *
     * @param \core\hook\navigation\site_administration_extend $hook
     */
    public static function callback(\core\hook\navigation\site_administration_extend $hook): void {
        global $CFG;

        $ADMIN = $hook->get_admin_root();
        $settings = $hook->create_settingpage_for_plugin(explode('\\', static::class)[0]);

        if ($settings && $ADMIN->fulltree) {
            require_once("$CFG->libdir/resourcelib.php");

            $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
            $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

            // General settings.
            $settings->add(new admin_setting_configmultiselect('page/displayoptions',
                get_string('displayoptions', 'page'), get_string('configdisplayoptions', 'page'),
                $defaultdisplayoptions, $displayoptions));

            // Modedit defaults.
            $settings->add(new admin_setting_heading('pagemodeditdefaults', get_string('modeditdefaults', 'admin'),
                get_string('condifmodeditdefaults', 'admin')));

            $settings->add(new admin_setting_configcheckbox('page/printintro',
                get_string('printintro', 'page'), get_string('printintroexplain', 'page'), 0));
            $settings->add(new admin_setting_configcheckbox('page/printlastmodified',
                get_string('printlastmodified', 'page'), get_string('printlastmodifiedexplain', 'page'), 1));
            $settings->add(new admin_setting_configselect('page/display',
                get_string('displayselect', 'page'), get_string('displayselectexplain', 'page'), RESOURCELIB_DISPLAY_OPEN,
                $displayoptions));
            $settings->add(new admin_setting_configtext('page/popupwidth',
                get_string('popupwidth', 'page'), get_string('popupwidthexplain', 'page'), 620, PARAM_INT, 7));
            $settings->add(new admin_setting_configtext('page/popupheight',
                get_string('popupheight', 'page'), get_string('popupheightexplain', 'page'), 450, PARAM_INT, 7));
        }
    }
}
