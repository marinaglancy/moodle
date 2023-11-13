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

namespace core\hook\navigation;

use core\context\system;
use core\hook\described_hook;
use core_collator;
use core_plugin_manager;
use part_of_admin_tree;

/**
 * Extend Site administration tree
 *
 * @package    core
 * @copyright  2023 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_administration_extend implements described_hook {

    /**
     * Creates new hook.
     *
     * @param \admin_root $admin Admin root
    */
    public function __construct(protected \admin_root $adminroot, protected array $pluginttypesections) {
    }

    /**
     * Admin root
     *
     * @return \admin_root
     */
    public function get_admin_root(): \admin_root {
        return $this->adminroot;
    }

    /**
     * Does the current user have capability to change site configuration?
     *
     * @return bool
     */
    public function has_site_config(): bool {
        return has_capability('moodle/site:config', system::instance());
    }

    /**
     * Describes the hook purpose.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Extend Site administration tree';
    }

    /**
     * List of tags that describe this hook.
     *
     * @return string[]
     */
    public static function get_hook_tags(): array {
        return ['navigation'];
    }

    /**
     * Should be called by the plugins that define subplugin types to annotate their settings
     *
     * @param string $type
     * @param string $sectionname
     * @return void
     */
    public function add_plugintype_section(string $type, string $sectionname) {
        $this->pluginttypesections[$type] = $sectionname;
    }

    /** @var part_of_admin_tree[] $pluginsettings */
    protected $pluginsettings = [];

    /**
     * Creates a section for the plugin settings under the plugin type section
     *
     * To be used by the plugin types that have plugin management table, for example:
     * mod, enrol, auth, block, format, customfield, editor, etc.
     * Often subplugintypes as well - assignfeedback, assignsubmission, atto, tiny, etc.
     * Moodle expects that the settings pages for these plugins have a specific name
     * and are located under the specific plugintype parent.
     *
     * Examples of plugin types that DO NOT have plugin management tables and should not call
     * this method: tool, local, report, theme.
     *
     * Plugins with complicated settings tree may use set_custom_settingpage_for_plugin() instead
     *
     * @param string $pluginname
     * @return \admin_settingpage|null
     */
    public function create_settingpage_for_plugin(string $pluginname): ?\admin_settingpage {
        [$type, $name] = \core_component::normalize_component($pluginname);
        $pluginname = "{$type}_{$name}";
        if (array_key_exists($pluginname, $this->pluginsettings)) {
            return $this->pluginsettings[$pluginname];
        }
        $this->pluginsettings[$pluginname] = null;

        $parentnodename = $this->pluginttypesections[$type] ?? null;

        if (!$parentnodename) {
            return null;
        }

        $plugininfo = core_plugin_manager::instance()->get_plugin_info($pluginname);

        if (!$plugininfo->is_installed_and_upgraded()) {
            return null;
        }

        if (!$this->has_site_config()) {
            return null;
        }

        $section = $plugininfo->get_settings_section_name();
        $settings = new \admin_settingpage(
            $section,
            $plugininfo->displayname,
            'moodle/site:config',
            $plugininfo->is_enabled() === false);

        $this->set_custom_settingpage_for_plugin($pluginname, $settings);
        return $settings;
    }

    /**
     * For complext plugins that need to add their settings to the tree in a custom way
     *
     * @param string $pluginname
     * @param part_of_admin_tree $settings
     * @return void
     */
    public function set_custom_settingpage_for_plugin(string $pluginname, part_of_admin_tree $settings): void {
        $this->pluginsettings[$pluginname] = $settings;
    }

    /**
     * Executed after the hook was dispatched - combines together added plugin settings and calls legacy loader
     *
     * @return void
     */
    public function post_dispatch(): void {
        foreach ($this->pluginttypesections as $type => $parentnodename) {
            /** @var \core\plugininfo\base[] $plugins */
            $plugins = core_plugin_manager::instance()->get_plugins_of_type($type);
            core_collator::asort_objects_by_property($plugins, 'displayname');
            foreach ($plugins as $plugin) {
                $settings = $this->pluginsettings[$plugin->component] ?? null;
                if ($settings) {
                    // Plugin settings node was added by the hook callback.
                   $this->get_admin_root()->add($parentnodename, $settings);
                } else {
                    // Fall back to loading settings from settings.php
                    $plugin->load_settings($this->get_admin_root(), $parentnodename, $this->has_site_config());
                }
            }
        }
    }
}
