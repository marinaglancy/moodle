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
 * external API for core library
 *
 * @package    core_webservice
 * @category   external
 * @copyright  2012 Jerome Mouneyrac <jerome@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * Web service related functions
 *
 * @package    core
 * @category   external
 * @copyright  2012 Jerome Mouneyrac <jerome@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.4
 */
class core_external extends external_api {


    /**
     * Format the received string parameters to be sent to the core get_string() function.
     *
     * @param array $stringparams
     * @return object|string
     * @since Moodle 2.4
     */
    public static function format_string_parameters($stringparams) {
        // Check if there are some string params.
        $strparams = new stdClass();
        if (!empty($stringparams)) {
            // There is only one string parameter.
            if (count($stringparams) == 1) {
                $stringparam = array_pop($stringparams);
                if (isset($stringparam['name'])) {
                    $strparams->{$stringparam['name']} = $stringparam['value'];
                } else {
                    // It is a not named string parameter.
                    $strparams = $stringparam['value'];
                }
            }  else {
                // There are more than one parameter.
                foreach ($stringparams as $stringparam) {

                    // If a parameter is unnamed throw an exception
                    // unnamed param is only possible if one only param is sent.
                    if (empty($stringparam['name'])) {
                        throw new moodle_exception('unnamedstringparam', 'webservice');
                    }

                    $strparams->{$stringparam['name']} = $stringparam['value'];
                }
            }
        }
        return $strparams;
    }

    /**
     * Returns description of get_string parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.4
     */
    public static function get_string_parameters() {
        return new external_function_parameters(
            array('stringid' => new external_value(PARAM_STRINGID, 'string identifier'),
                  'component' => new external_value(PARAM_COMPONENT,'component', VALUE_DEFAULT, 'moodle'),
                  'lang' => new external_value(PARAM_LANG, 'lang', VALUE_DEFAULT, null),
                  'stringparams' => new external_multiple_structure (
                      new external_single_structure(array(
                          'name' => new external_value(PARAM_ALPHANUMEXT, 'param name
                            - if the string expect only one $a parameter then don\'t send this field, just send the value.', VALUE_OPTIONAL),
                          'value' => new external_value(PARAM_TEXT,'param value'))),
                          'the definition of a string param (i.e. {$a->name})', VALUE_DEFAULT, array()
                   )
            )
        );
    }

    /**
     * Return a core get_string() call
     *
     * @param string $identifier string identifier
     * @param string $component string component
     * @param array $stringparams the string params
     * @return string
     * @since Moodle 2.4
     */
    public static function get_string($stringid, $component = 'moodle', $lang = null, $stringparams = array()) {
        $params = self::validate_parameters(self::get_string_parameters(),
                      array('stringid'=>$stringid, 'component' => $component, 'lang' => $lang, 'stringparams' => $stringparams));

        $stringmanager = get_string_manager();
        return $stringmanager->get_string($params['stringid'], $params['component'],
            core_external::format_string_parameters($params['stringparams']), $params['lang']);
    }

    /**
     * Returns description of get_string() result value
     *
     * @return string
     * @since Moodle 2.4
     */
    public static function get_string_returns() {
        return new external_value(PARAM_TEXT, 'translated string');
    }

    /**
     * Returns description of get_string parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.4
     */
    public static function get_strings_parameters() {
        return new external_function_parameters(
            array('strings' => new external_multiple_structure (
                    new external_single_structure (array(
                        'stringid' => new external_value(PARAM_STRINGID, 'string identifier'),
                        'component' => new external_value(PARAM_COMPONENT, 'component', VALUE_DEFAULT, 'moodle'),
                        'lang' => new external_value(PARAM_LANG, 'lang', VALUE_DEFAULT, null),
                        'stringparams' => new external_multiple_structure (
                            new external_single_structure(array(
                                'name' => new external_value(PARAM_ALPHANUMEXT, 'param name
                                    - if the string expect only one $a parameter then don\'t send this field, just send the value.', VALUE_OPTIONAL),
                                'value' => new external_value(PARAM_TEXT, 'param value'))),
                                'the definition of a string param (i.e. {$a->name})', VALUE_DEFAULT, array()
                        ))
                    )
                )
            )
        );
    }

    /**
     * Return multiple call to core get_string()
     *
     * @param array $strings strings to translate
     * @return array
     *
     * @since Moodle 2.4
     */
    public static function get_strings($strings) {
        $params = self::validate_parameters(self::get_strings_parameters(),
                      array('strings'=>$strings));
        $stringmanager = get_string_manager();

        $translatedstrings = array();
        foreach($params['strings'] as $string) {

            if (!empty($string['lang'])) {
                $lang = $string['lang'];
            } else {
                $lang = current_language();
            }

            $translatedstrings[] = array(
                'stringid' => $string['stringid'],
                'component' => $string['component'],
                'lang' => $lang,
                'string' => $stringmanager->get_string($string['stringid'], $string['component'],
                    core_external::format_string_parameters($string['stringparams']), $lang));
        }

        return $translatedstrings;
    }

    /**
     * Returns description of get_string() result value
     *
     * @return array
     * @since Moodle 2.4
     */
    public static function get_strings_returns() {
        return new external_multiple_structure(
            new external_single_structure(array(
                'stringid' => new external_value(PARAM_STRINGID, 'string id'),
                'component' => new external_value(PARAM_COMPONENT, 'string component'),
                'lang' => new external_value(PARAM_LANG, 'lang'),
                'string' => new external_value(PARAM_TEXT, 'translated string'))
            ));
    }

     /**
     * Returns description of get_component_strings parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.4
     */
    public static function get_component_strings_parameters() {
        return new external_function_parameters(
            array('component' => new external_value(PARAM_COMPONENT, 'component'),
                  'lang' => new external_value(PARAM_LANG, 'lang', VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Return all lang strings of a component - call to core get_component_strings().
     *
     * @param string $component component name
     * @return array
     *
     * @since Moodle 2.4
     */
    public static function get_component_strings($component, $lang = null) {

        if (empty($lang)) {
            $lang = current_language();
        }

        $params = self::validate_parameters(self::get_component_strings_parameters(),
                      array('component'=>$component, 'lang' => $lang));

        $stringmanager = get_string_manager();

        $wsstrings = array();
        $componentstrings = $stringmanager->load_component_strings($params['component'], $params['lang']);
        foreach($componentstrings as $stringid => $string) {
            $wsstring = array();
            $wsstring['stringid'] = $stringid;
            $wsstring['string'] = $string;
            $wsstrings[] = $wsstring;
        }

        return $wsstrings;
    }

    /**
     * Returns description of get_component_strings() result value
     *
     * @return array
     * @since Moodle 2.4
     */
    public static function get_component_strings_returns() {
        return new external_multiple_structure(
            new external_single_structure(array(
                'stringid' => new external_value(PARAM_STRINGID, 'string id'),
                'string' => new external_value(PARAM_RAW, 'translated string'))
            ));
    }

    /**
     * Parameters for function update_generic_title()
     *
     * @return external_function_parameters
     */
    public static function update_generic_title_parameters() {
        return new external_function_parameters(
            array(
                'callback' => new external_value(PARAM_NOTAGS, 'callback for updating', VALUE_REQUIRED),
                'value' => new external_value(PARAM_RAW, 'new value', VALUE_REQUIRED),
                'identifier' => new external_value(PARAM_RAW, 'identifier of the updated instance', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Update generic title
     *
     * @param string $callback
     * @param string $value
     * @param string $identifier
     */
    public static function update_generic_title($callback, $value, $identifier) {
        global $PAGE;
        // Validate and normalize parameters.
        $params = self::validate_parameters(self::update_generic_title_parameters(),
                      array('callback' => $callback, 'value' => $value, 'identifier' => $identifier));
        $callback = $params['callback'];
        $value = $params['value'];
        $identifier = $params['identifier'];
        if (!class_exists($callback) || !is_subclass_of($callback, 'core\\output\\editabletitle')) {
            throw new \coding_exception('Class ' . $callback . ' not found or is not allowed here');
        }
        $obj = new $callback($identifier);
        $obj->update($value);
        $renderer = $PAGE->get_renderer('core');
        return $obj->export_for_template($renderer);
    }

    /**
     * Return structure for update_generic_title()
     *
     * @return external_description
     */
    public static function update_generic_title_returns() {
        return new external_single_structure(
            array(
                'displayvalue' => new external_value(PARAM_RAW, 'display value'),
                'callback' => new external_value(PARAM_NOTAGS, 'class name responsible for the display and update'),
                'value' => new external_value(PARAM_RAW, 'value'),
                'identifier' => new external_value(PARAM_RAW, 'identifier of the instance'),
                'edithint' => new external_value(PARAM_NOTAGS, 'hint for editing element'),
                'hash' => new external_value(PARAM_NOTAGS, 'unique hash of component and identifier'),
                'editlabel' => new external_value(PARAM_NOTAGS, 'label for editing element'),
            )
        );
    }
}
