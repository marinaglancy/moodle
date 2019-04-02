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
 * Class testform
 *
 * @package     core_tag
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_tag;

use core_form\modal;

defined('MOODLE_INTERNAL') || die();

/**
 * Class testform
 *
 * @package     core_tag
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testform extends modal {

    protected function get_context_for_ajax_submission(): \context {
        return \context_system::instance();
    }

    protected function check_access_for_ajax_submission() {

    }

    public function set_data_for_ajax_submission() {
        $this->set_data(['hidebuttons' => !empty($this->_ajaxformdata['hidebuttons'])]);
    }

    public function process_ajax_submission()
    {
        return $this->get_data();
    }

    public function definition() {
        $mform = $this->_form;

        // Required field (client-side validation test).

        $mform->addElement('text', 'name', get_string('fieldname', 'core_customfield'), 'size="50"');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);


        // Repeated elements.

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('text', 'option', get_string('optionno', 'choice'));
        $mform->setType('option', PARAM_CLEANHTML);
        $mform->setType('optionid', PARAM_INT);

        $this->repeat_elements($repeatarray, 1,
            [], 'option_repeats', 'option_add_fields', 1, null, true);


        // Editor.

        $desceditoroptions = $this->get_description_text_options();
        $mform->addElement('editor', 'description_editor', get_string('description', 'core_customfield'), null, $desceditoroptions);
        $mform->addHelpButton('description_editor', 'description', 'core_customfield');

        // Buttons.

        $mform->addElement('hidden', 'hidebuttons');
        $mform->setType('hidebuttons', PARAM_BOOL);
        if (empty($this->_ajaxformdata['hidebuttons'])) {
            $this->add_action_buttons();
        }
    }

    public function get_description_text_options() : array {
        global $CFG;
        require_once($CFG->libdir.'/formslib.php');
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'context' => \context_system::instance()
        ];
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     * If the form has elements sensitive to the page url this method must be overridden
     *
     * Note: autosave function in Atto 'editor' elements is sensitive to page url
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_ajax_submission(): \moodle_url {
        return new \moodle_url('/tag/test.php');
    }

}
