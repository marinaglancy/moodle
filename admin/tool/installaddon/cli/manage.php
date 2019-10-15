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
 * Allows to install and upgrade addons
 *
 * @package    tool_installaddon
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir. '/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'plugins' => false,
        'version' => false,
        'install' => false,
        'upgrade' => false,
        'zipfilepath' => false,
        'help' => false
    ],
    [
        'h' => 'help',
        'p' => 'plugins',
        'v' => 'version',
        'i' => 'install',
        'u' => 'upgrade',
        'z' => 'zipfilepath'
    ]
);

if ($options['help'] || (empty($options['plugins']) && empty($options['zipfilepath']))) {
    $help =
        "Validates add-ons and copies the source code into appropriate directory.

Important! It is still necessary to run Moodle upgrade script to complete installation.

This script can work with the Moodle plugins directory https://moodle.org/plugins or with the .zip files.

If you want to fetch a plugin from a git repository, you can use respective APIs to download code as a zip file or clone
the repository locally and use 'git archive' to assemble an archive.

Either --plugin or --zipfilepath must be specified.

If --plugins=all is specified, the script will try to upgrade all installed add-ons if they are present in the
plugins directory and if the latest version in plugins directory is not lower than the current. All other add-ons
will be skipped.

If specific plugins or zipfilepath is specified, the script will exit if at least one of the plugins is not
found or can not be upgraded/installed.

Options:
-p, --plugins         Names of the add-ons (comma-separated values or 'all' for all installed)
-v, --version         Specific version of the add-ons, only for plugins from https://moodle.org/plugins
-z, --zipfilepath     Path to .zip file in the local FS with the add-on source code
-i, --install         Installs the specified add-ons code
-u, --upgrade         Upgrades the specified add-ons code
-h, --help            Print out this help

Examples:
(Note, unlike other CLI scripts, you need to run this script as a user who owns files in wwwroot and not as www-data)

Check available upgrades:
\$/usr/bin/php admin/tool/installaddon/cli/manage.php --plugins=all

Install given plugins with a given version:
\$/usr/bin/php admin/tool/installaddon/cli/manage.php --plugins=mod_supermodule,tool_supertool --version=2019010100 --install

Upgrade all installed plugins that can be upgraded:
\$/usr/bin/php admin/tool/installaddon/cli/manage.php --plugins=all --upgrade

Installs or upgrades a plugin from a zip:
\$wget https://github.com/moodlehq/moodle-local_codechecker/archive/master.zip --output-document=/tmp/local_codechecker.zip
\$/usr/bin/php admin/tool/installaddon/cli/manage.php --zipfilepath=/tmp/local_codechecker.zip --install --upgrade

";

    echo $help;
    die;
}

$manager = core_plugin_manager::instance();
$installedplugins = tool_installaddons_get_list_of_non_standard_plugins($manager);

$validated = true;
if ($options['plugins'] === 'all') {
    $pluginslist = array_keys($installedplugins);
} else {
    $pluginslist = [];
    foreach (preg_split('/\s*,\s*/', trim($options['plugins']), -1, PREG_SPLIT_NO_EMPTY) as $pluginname) {
        if ($pluginname !== clean_param($pluginname, PARAM_COMPONENT)) {
            mtrace("[ERROR] $pluginname is not a valid plugin name");
            $validated = false;
        } else {
            $pluginslist[] = $pluginname;
        }
    }
}

$version = $options['version'] ?: ANY_VERSION;

$pluginstovalidate = [];
$plugins = [];

// First try to extract plugin from zipfilepath. Any error here will stop the script.
if ($options['zipfilepath']) {
    $filepath = $options['zipfilepath'];
    if (!file_exists($filepath)) {
        mtrace("[Error] File with path $filepath not found");
        $validated = false;
    } else {
        $installer = tool_installaddon_installer::instance();
        $pluginname = $installer->detect_plugin_component($filepath);
        if (empty($pluginname)) {
            mtrace("[Error] Can not find a Moodle plugin in $filepath. Exiting");
            $validated = false;
        } else {
            $isinstalled = isset($installedplugins[$pluginname]) ? (int)$installedplugins[$pluginname] : false;
            $proceed = true;
            if (!$isinstalled) {
                $proceed = (bool)$options['install'];
            } else if (($version = tool_installaddons_get_version($filepath)) && (int)$version < $isinstalled) {
                // TODO MDL-66917 we need to call some API method to check the downgrade.
                mtrace("[Error] $pluginname: cannotdowngrade (". get_string('status_downgrade', 'core_plugin') . ")");
                $validated = false;
                $proceed = false;
            } else {
                $proceed = (bool)$options['upgrade'];
            }
            $plugin = (object)['component' => $pluginname, 'zipfilepath' => $filepath];
            $pluginstovalidate[] = $plugin;
            if ($proceed) {
                $plugins[] = $plugin;
            }
            // Prevent pulling the same plugin from plugins directory.
            $pluginslist = array_diff($pluginslist, [$pluginname]);
        }
    }
}

// This script should work even if $CFG->disableupdateautodeploy is on.
$olddisableupdateautodeploy = isset($CFG->disableupdateautodeploy) ? $CFG->disableupdateautodeploy : null;
$CFG->disableupdateautodeploy = false;

try {
    foreach ($pluginslist as $pluginname) {
        $reason = '';
        $plugin = $manager->get_remote_plugin_info($pluginname, $version, $version != ANY_VERSION);
        $isinstalled = isset($installedplugins[$pluginname]) ? (int)$installedplugins[$pluginname] : false;
        $error = null;
        if (!$plugin) {
            $error = "Plugin not found in plugins directory";
        } else if (!$plugin->version) {
            $error = "Version not found in plugins directory";
        } else if (!$manager->is_remote_plugin_installable($pluginname, $plugin->version->version, $reason)) {
            if ($reason === 'notwritableplugintype' or $reason === 'notwritableplugin') {
                $reason .= " (". get_string('notwritable', 'core_plugin') . ")";
            } else if ($reason === 'remoteunavailable') {
                $reason .= " (". get_string('notdownloadable', 'core_plugin') . ")";
            } else if ($reason === 'cannotdowngrade') {
                $reason .= " (". get_string('status_downgrade', 'core_plugin') . ")";
            }
            $error = $reason;
        }

        if ($error) {
            // If we request "all" plugins we only use warning for errors, otherwise we fail validation.
            if ($options['plugins'] === 'all') {
                mtrace("[Warning] $pluginname: $error");
            } else {
                mtrace("[Error] $pluginname: $error");
                $validated = false;
            }
        } else {
            if (!$isinstalled) {
                $proceed = (bool)$options['install'];
            } else {
                $proceed = (bool)$options['upgrade'];
            }
            $pluginstovalidate[] = $manager->get_remote_plugin_info($pluginname, $plugin->version->version, true);
            if ($proceed) {
                $plugins[] = $manager->get_remote_plugin_info($pluginname, $plugin->version->version, true);
            }
        }
    }

    if ($pluginstovalidate) {
        if (!$manager->install_plugins($pluginstovalidate, false, false)) {
            $validated = false;
        }
    }
    if (!$validated) {
        mtrace("\n\nValidation failed for at least one plugin, no plugins will be installed or upgraded");
    }
    if ($validated && $plugins) {
        $manager->install_plugins($plugins, true, false);
        mtrace("\n\nDone. Visit the website as admin or run Moodle CLI upgrade script:");
        mtrace("    sudo -u www-data /usr/bin/php admin/cli/upgrade.php");
    }
} catch (Exception $e) {
    mtrace("Exception occurred: ".$e->getMessage());
    $validated = false;
}

if ($olddisableupdateautodeploy === null) {
    unset($CFG->disableupdateautodeploy);
} else {
    $CFG->disableupdateautodeploy = $olddisableupdateautodeploy;
}

if (!$validated) {
    die(1);
}

/**
 * Retrieves the list of all non-standard plugins with their current versions
 *
 * @param core_plugin_manager $manager
 * @return array
 */
function tool_installaddons_get_list_of_non_standard_plugins(core_plugin_manager $manager) {
    $plugins = [];
    foreach (array_keys($manager->get_plugin_types()) as $plugintype) {
        $installed = $manager->get_installed_plugins($plugintype);
        $standard = core_plugin_manager::standard_plugins_list($plugintype) ?: [];
        foreach ($installed as $plugin => $version) {
            if (!in_array($plugin, $standard)) {
                $plugins[$plugintype . '_' . $plugin] = $version;
            }
        }
    }
    return $plugins;
}

/**
 * Get version number from the archive
 *
 * @param string $zipfile
 * @return string|false
 */
function tool_installaddons_get_version($zipfile) {
    // TODO MDL-66917 remove when fixed.
    $tmp = make_request_directory();
    core_plugin_manager::instance()->unzip_plugin_file($zipfile, $tmp, 'plugin');
    $fullpath = $tmp.'/plugin/version.php';
    if (file_exists($fullpath)) {
        $versionfile = file_get_contents($fullpath);
        preg_match('#\$(plugin|module)\->version\s*=\s*(.*?)\s*;#', $versionfile, $matches1);
    }
    return !empty($matches1[2]) ? $matches1[2] : false;
}