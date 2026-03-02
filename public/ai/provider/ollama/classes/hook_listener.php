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

namespace aiprovider_ollama;

use core_ai\hook\after_ai_action_settings_form_hook;
use core_ai\hook\after_ai_provider_form_hook;

/**
 * Hook listener for Ollama Provider.
 *
 * @package    aiprovider_ollama
 * @copyright  2025 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Hook listener for the Ollama instance setup form.
     *
     * @param after_ai_provider_form_hook $hook The hook to add to the AI instance setup.
     */
    public static function set_form_definition_for_aiprovider_ollama(after_ai_provider_form_hook $hook): void {
        if ($hook->plugin !== 'aiprovider_ollama') {
            return;
        }

        $mform = $hook->mform;

        // Setting to store Ollama endpoint URL.
        $mform->addElement(
            'text',
            'endpoint',
            get_string('endpoint', 'aiprovider_ollama'),
            ['size' => 25],
        );
        $mform->setType('endpoint', PARAM_URL);
        $mform->addHelpButton('endpoint', 'endpoint', 'aiprovider_ollama');
        $mform->addRule('endpoint', get_string('required'), 'required', null, 'client');
        $mform->setDefault('endpoint', 'http://localhost:11434');

        // Authentication type selector.
        $authtypes = [
            'none' => get_string('authtype_none', 'aiprovider_ollama'),
            'basic' => get_string('authtype_basic', 'aiprovider_ollama'),
            'bearer' => get_string('authtype_bearer', 'aiprovider_ollama'),
        ];
        $mform->addElement(
            'select',
            'authtype',
            get_string('authtype', 'aiprovider_ollama'),
            $authtypes,
        );
        $mform->setType('authtype', PARAM_ALPHA);
        $mform->addHelpButton('authtype', 'authtype', 'aiprovider_ollama');
        $mform->setDefault('authtype', 'none');

        // Username for basic auth.
        $mform->addElement(
            'text',
            'username',
            get_string('username', 'aiprovider_ollama'),
        );
        $mform->setType('username', PARAM_TEXT);
        $mform->addHelpButton('username', 'username', 'aiprovider_ollama');
        $mform->hideIf('username', 'authtype', 'neq', 'basic');

        // Password for basic auth.
        $mform->addElement(
            'passwordunmask',
            'password',
            get_string('password', 'aiprovider_ollama'),
        );
        $mform->setType('password', PARAM_TEXT);
        $mform->addHelpButton('password', 'password', 'aiprovider_ollama');
        $mform->hideIf('password', 'authtype', 'neq', 'basic');

        // API key for bearer token auth.
        $mform->addElement(
            'passwordunmask',
            'apikey',
            get_string('apikey', 'aiprovider_ollama'),
        );
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey', 'aiprovider_ollama');
        $mform->hideIf('apikey', 'authtype', 'neq', 'bearer');
    }

    /**
     * Hook listener for the Ollama action settings form.
     *
     * @param after_ai_action_settings_form_hook $hook The hook to add to config action settings.
     */
    public static function set_model_form_definition_for_aiprovider_ollama(after_ai_action_settings_form_hook $hook): void {
        if ($hook->plugin !== 'aiprovider_ollama') {
            return;
        }

        $mform = $hook->mform;
        if (isset($mform->_elementIndex['modeltemplate'])) {
            $model = $mform->getElementValue('modeltemplate');
            if (is_array($model)) {
                $model = $model[0];
            }

            if ($model == 'custom') {
                $mform->addElement('header', 'modelsettingsheader', get_string('settings', 'aiprovider_ollama'));
                $settingshelp = \html_writer::tag('p', get_string('settings_help', 'aiprovider_ollama'));
                $mform->addElement('html', $settingshelp);
                $mform->addElement(
                    'textarea',
                    'modelextraparams',
                    get_string('extraparams', 'aiprovider_ollama'),
                    ['rows' => 5, 'cols' => 20],
                );
                $mform->setType('modelextraparams', PARAM_TEXT);
                $mform->addElement('static', 'modelextraparams_help', null, get_string('extraparams_help', 'aiprovider_ollama'));
            } else {
                $targetmodel = helper::get_model_class($model);
                if ($targetmodel) {
                    if ($targetmodel->has_model_settings()) {
                        $mform->addElement('header', 'modelsettingsheader', get_string('settings', 'aiprovider_ollama'));
                        $settingshelp = \html_writer::tag('p', get_string('settings_help', 'aiprovider_ollama'));
                        $mform->addElement('html', $settingshelp);
                        $targetmodel->add_model_settings($mform);
                    }
                }
            }
        }
    }
}
