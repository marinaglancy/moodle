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
 * Provides the core_form\modal class.
 *
 * @package     core_form
 * @copyright   2020 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Class modal
 *
 * Extend this class to create a form that can be used in a modal dialogue.
 *
 * @package     core_form
 * @copyright   2020 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class modal extends \moodleform {

    /**
     * Constructor for modal forms can not be overridden, however the same form can be used both in AJAX and normally
     *
     * @param string $action
     * @param array $customdata
     * @param string $method
     * @param string $target
     * @param array $attributes
     * @param bool $editable
     * @param array $ajaxformdata Forms submitted via ajax, must pass their data here, instead of relying on _GET and _POST.
     * @param bool $isajaxsubmission whether the form is called from WS and it needs to validate user access and set up context
     */
    final public function __construct(?string $action = null, ?array $customdata = null, string $method = 'post',
                                      string $target = '', ?array $attributes = [], bool $editable = true,
                                      ?array $ajaxformdata = null, bool $isajaxsubmission = false) {
        global $PAGE, $CFG;
        $this->_ajaxformdata = $ajaxformdata;
        if ($isajaxsubmission) {
            require_once($CFG->libdir . '/externallib.php');
            // This form was created from the WS that needs to validate user access to it and set page context.
            // It has to be done before calling parent constructor because elements definitions may need to use
            // format_string functions and other methods that expect the page to be set up.
            \external_api::validate_context($this->get_context_for_ajax_submission());
            $PAGE->set_url($this->get_page_url_for_ajax_submission());
            $this->check_access_for_ajax_submission();
        }
        $attributes = ($attributes ?: []) + ['data-random-ids' => 1];
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     */
    abstract protected function check_access_for_ajax_submission();

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * Submission data can be accessed as: $this->get_data()
     *
     * @return mixed
     */
    abstract public function process_ajax_submission();

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     *
     * Example:
     *     $this->set_data(get_entity($this->_ajaxformdata['id']));
     */
    abstract public function set_data_for_ajax_submission();

    /**
     * Returns form context
     *
     * If context depends on the form data, it is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     *
     * @return \context
     */
    protected function get_context_for_ajax_submission(): \context {
        return \context_system::instance();
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * If the form has arguments (such as 'id' of the element being edited), the URL should
     * also have respective argument.
     *
     * @return \moodle_url
     */
    abstract protected function get_page_url_for_ajax_submission(): \moodle_url;
}
