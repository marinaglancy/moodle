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
 * Class core_form\modal
 *
 * @package     core_form
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Class core_form_modal
 *
 * Extend this class to create a form that can be used in a modal dialogue.
 *
 * Example of usage in javascript:
 *
 * require(['core_form/modal'], function(ModalForm) {
 *     $(selector).on('click', function(e) {
 *         var modal = new ModalForm({
 *             formClass: 'pluginname\\formname',
 *             args: {entityid: entityid},
 *             modalConfig: {title: Str.get_string('editentity', 'pluginname')},
 *             triggerElement: $(e.currentTarget)
 *         });
 *         // If necessary extend functionality by overriding class methods, for example:
 *         modal.onSubmitSuccess = function(response) {
 *             window.location.reload();
 *         };
 *     });
 * });
 *
 * @package     core_form
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class modal extends \moodleform {

    /**
     * Constructor for modal forms can not be overridden, however the same form can be used both in AJAX and normally
     *
     * @param mixed $action
     * @param mixed $customdata
     * @param string $method
     * @param string $target
     * @param mixed $attributes
     * @param bool $editable
     * @param array $ajaxformdata Forms submitted via ajax, must pass their data here, instead of relying on _GET and _POST.
     * @param bool $isajaxsubmission whether the form is called from WS and it needs to validate user access and set up context
     */
    public final function __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true,
                                ?array $ajaxformdata=null, bool $isajaxsubmission=false) {
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
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata
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
     * If context depends on the form data, it is available in $this->_ajaxformdata
     *
     * @return \context
     */
    abstract protected function get_context_for_ajax_submission(): \context;

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     * If the form has elements sensitive to the page url this method must be overridden
     *
     * Note: autosave function in Atto 'editor' elements is sensitive to page url
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_ajax_submission(): \moodle_url {
        return new \moodle_url('/');
    }
}
