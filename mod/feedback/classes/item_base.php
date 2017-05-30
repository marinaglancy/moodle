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
 * Contains class mod_feedback_item_base
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for plugins feedbackitem
 *
 * @package   mod_feedback
 * @copyright 2017 Marina Glancy
 * @author    Andreas Grabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mod_feedback_item_base {

    /** @var string type of the element, automatically determined from the class name */
    protected $type;

    /** @var mod_feedback_item_form */
    protected $item_form;

    /** @var stdClass */
    protected $item;

    /**
     * constructor
     */
    public function __construct() {
        if (preg_match('/^feedbackitem_(.*)_plugin$/', get_class($this), $matches)) {
            $this->type = $matches[1];
        } else {
            throw new coding_exception('Class ' . get_class($this) .
                    ' should overwrite the constructor to specify the item type.');
        }
    }

    /**
     * Displays the form for editing an item
     *
     * this function only can used after the call of build_editform()
     */
    public function show_editform() {
        $this->item_form->display();
    }

    /**
     * Checks if the editing form was cancelled
     *
     * @return bool
     */
    public function is_cancelled() {
        return $this->item_form->is_cancelled();
    }

    /**
     * Gets submitted data from the edit form and saves it in $this->item
     *
     * @return bool
     */
    public function get_data() {
        if ($this->item !== null) {
            return true;
        }
        if ($this->item = $this->item_form->get_data()) {
            return true;
        }
        return false;
    }

    /**
     * Set the item data (to be used by data generators).
     *
     * @param stdClass $itemdata the item data to set
     * @since Moodle 3.3
     */
    public function set_data($itemdata) {
        $this->item = $itemdata;
    }

    /**
     * Creates and returns an instance of the form for editing the item
     *
     * @param stdClass $item
     * @param stdClass $feedback
     * @param cm_info|stdClass $cm
     */
    abstract public function build_editform($item, $feedback, $cm);

    /**
     * Saves the item after it has been edited (or created)
     */
    abstract public function save_item();

    /**
     * Converts the value from complete_form data to the string value that is stored in the db.
     * @param mixed $value element from mod_feedback_complete_form::get_data() with the name $item->typ.'_'.$item->id
     * @return string
     */
    public function create_value($value) {
        return strval($value);
    }

    /**
     * Compares the dbvalue with the dependvalue
     *
     * @param stdClass $item
     * @param string $dbvalue is the value input by user in the format as it is stored in the db
     * @param string $dependvalue is the value that it needs to be compared against
     */
    public function compare_value($item, $dbvalue, $dependvalue) {
        return strval($dbvalue) === strval($dependvalue);
    }

    /**
     * Wether this item type has a value that is expected from the user and saved in the stored values.
     * @return int
     */
    public function get_hasvalue() {
        return 1;
    }

    /**
     * Wether this item can be set as both required and not
     * @return bool
     */
    public function can_switch_require() {
        return true;
    }

    /**
     * Adds summary information about an item to the Excel export file
     *
     * @param object $worksheet a reference to the pear_spreadsheet-object
     * @param integer $row_offset
     * @param stdClass $xls_formats see analysis_to_excel.php
     * @param object $item the db-object from feedback_item
     * @param integer $groupid
     * @param integer $courseid
     * @return integer the new row_offset
     */
    abstract public function excelprint_item(&$worksheet, $row_offset,
                                         $xls_formats, $item,
                                         $groupid, $courseid = false);

    /**
     * Prints analysis for the current item
     *
     * @param $item the db-object from feedback_item
     * @param string $itemnr
     * @param integer $groupid
     * @param integer $courseid
     * @return integer the new itemnr
     */
    abstract public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false);

    /**
     * Prepares the value for exporting to Excel
     *
     * @param object $item the db-object from feedback_item
     * @param string $value a item-related value from feedback_values
     * @return string
     */
    abstract public function get_printval($item, $value);

    /**
     * Returns the formatted name of the item for the complete form or response view
     *
     * @param stdClass $item
     * @param bool $withpostfix
     * @param bool $truncated
     * @return string
     */
    public function get_display_name($item, $withpostfix = true, $truncated = false) {
        $name = $item->name;
        if ($nameoptions = $this->get_name_editor_options($item)) {
            $name = file_rewrite_pluginfile_urls($item->name, 'pluginfile.php',
                $nameoptions['context']->id, 'mod_feedback',
                $this->get_file_area($item), $item->id, $nameoptions);
        }
        $name = format_text($name, $item->nameformat, array('noclean' => true, 'para' => false));
        $postfix = ($withpostfix ? $this->get_display_name_postfix($item) : '');
        if ($truncated) {
            return shorten_text(trim(html_to_text($name)), 72) . html_to_text($postfix);
        } else {
            return $name . $postfix;
        }
    }

    /**
     * Finds the context where current item lives
     *
     * We try to do it with least possible number of DB queries
     *
     * @param stdClass $item
     * @return context
     */
    protected function get_context($item) {
        global $DB, $PAGE;
        if (empty($item->feedback) AND !empty($item->template)) {
            $template = $DB->get_record('feedback_template', array('id' => $item->template));
            if ($template->ispublic) {
                $context = context_system::instance();
            } else {
                $context = context_course::instance($template->course);
            }
        } else if (!empty($item->cmid)) {
            $context = context_module::instance($item->cmid);
        } else if ($PAGE->cm && $PAGE->cm->instance == $item->feedback && $PAGE->cm->modname === 'feedback') {
            $context = $PAGE->cm->context;
        } else {
            $cm = get_coursemodule_from_instance('feedback', $item->feedback);
            $context = context_module::instance($cm->id);
            $item->cmid = $cm->id;
        }
        return $context;
    }

    /**
     * Finds the file area for files embedded in this item's name - "template" or "item"
     *
     * @param stdClass $item
     * @return string
     */
    protected function get_file_area($item) {
        if (!$item->feedback AND $item->template) {
            return 'template';
        } else {
            return 'item';
        }
    }

    /**
     * Options for HTML editor for the 'name' element
     *
     * Items that do not want to have HTML editor for the name should return overwrite and return null
     *
     * @param stdClass $item
     * @return array
     */
    protected function get_name_editor_options($item) {
        return array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext' => true, 'context' => $this->get_context($item));
    }

    /**
     * Returns the postfix to be appended to the display name that is based on other settings
     *
     * @param stdClass $item
     * @return string
     */
    public function get_display_name_postfix($item) {
        return '';
    }

    /**
     * Adds an input element to the complete form
     *
     * This method is called:
     * - to display the form when user completes feedback
     * - to display existing elements when teacher edits the feedback items
     * - to display the feedback preview (print.php)
     * - to display the completed response
     * - to preview a feedback template
     *
     * If it is important which mode the form is in, use $form->get_mode()
     *
     * Each item type must add a single form element with the name $item->typ.'_'.$item->id
     * This element must always be present in form data even if nothing is selected (i.e. use advcheckbox and not checkbox).
     * To add an element use either:
     * $form->add_form_element() - adds a single element to the form
     * $form->add_form_group_element() - adds a group element to the form
     *
     * Other useful methods:
     * $form->get_item_value()
     * $form->set_element_default()
     * $form->add_validation_rule()
     * $form->set_element_type()
     *
     * The element must support freezing so it can be used for viewing the response as well.
     * If the desired form element does not support freezing, check $form->is_frozen()
     * and create a static element instead.
     *
     * @param stdClass $item
     * @param mod_feedback_complete_form $form
     */
    abstract public function complete_form_element($item, $form);

    /**
     * Returns the list of actions allowed on this item in the edit mode
     *
     * @param stdClass $item
     * @param stdClass $feedback
     * @param cm_info $cm
     * @return action_menu_link[]
     */
    public function edit_actions($item, $feedback, $cm) {
        $actions = array();

        $strupdate = get_string('edit_item', 'feedback');
        $actions['update'] = new action_menu_link_secondary(
            new moodle_url('/mod/feedback/edit_item.php', array('id' => $item->id)),
            new pix_icon('t/edit', $strupdate, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $strupdate,
            array('class' => 'editing_update', 'data-action' => 'update')
        );

        if ($this->can_switch_require()) {
            if ($item->required == 1) {
                $buttontitle = get_string('switch_item_to_not_required', 'feedback');
                $buttonimg = 'required';
            } else {
                $buttontitle = get_string('switch_item_to_required', 'feedback');
                $buttonimg = 'notrequired';
            }
            $actions['required'] = new action_menu_link_secondary(
                new moodle_url('/mod/feedback/edit.php', array('id' => $cm->id,
                    'switchitemrequired' => $item->id, 'sesskey' => sesskey())),
                new pix_icon($buttonimg, $buttontitle, 'feedback', array('class' => 'iconsmall', 'title' => '')),
                $buttontitle,
                array('class' => 'editing_togglerequired', 'data-action' => 'togglerequired')
            );
        }

        $strdelete = get_string('delete_item', 'feedback');
        $actions['delete'] = new action_menu_link_secondary(
            new moodle_url('/mod/feedback/edit.php', array('id' => $cm->id, 'deleteitem' => $item->id, 'sesskey' => sesskey())),
            new pix_icon('t/delete', $strdelete, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $strdelete,
            array('class' => 'editing_delete', 'data-action' => 'delete')
        );

        return $actions;
    }

    /**
     * Return extra data for external functions.
     *
     * Some items may have additional configuration data or default values that should be returned for external functions:
     * - Info elements: The default value information (course or category name)
     * - Captcha: The recaptcha challenge hash key
     *
     * @param stdClass $item the item object
     * @return str the data, may be json_encoded for large structures
     */
    public function get_data_for_external($item) {
        return null;
    }

    /**
     * Return the analysis data ready for external functions.
     *
     * @param stdClass $item     the item (question) information
     * @param int      $groupid  the group id to filter data (optional)
     * @param int      $courseid the course id (optional)
     * @return array an array of data with non scalar types json encoded
     * @since  Moodle 3.3
     */
    abstract public function get_analysed_for_external($item, $groupid = false, $courseid = false);

    /**
     * Prepares the item for export
     *
     * @param stdClass $item
     * @return stdClass|array element ready to be exported, fields id and typ will be added automatically
     */
    public function prepare_for_export($item) {
        static $fields = ['name', 'nameformat', 'label', 'presentation', 'options', 'required', 'dependitem', 'dependvalue'];
        $item = array_intersect_key((array)$item, array_fill_keys($fields, 1));
        if (empty($item['dependitem'])) {
            unset($item['dependitem']);
            unset($item['dependvalue']);
        }
        if (empty($item['required'])) {
            unset($item['required']);
        }
        foreach (['presentation', 'label', 'options'] as $property) {
            if (strval($item[$property]) === '') {
                unset($item[$property]);
            }
        }
        return $item;
    }

    /**
     * Prepares a record to be inserted in the DB when importing feedback item
     *
     * @param array $data item data from the XML file, result of xmlize()
     * @param stdClass $feedback
     * @param int $version version of the export file
     * @param array $idsmap map of item ids in the export file and in the current feedback (for restoring dependencies)
     * @return stdClass|array
     */
    protected function prepare_for_import($data, $feedback, $version, $idsmap) {
        static $fields = ['name', 'nameformat', 'label', 'presentation', 'options', 'required', 'dependitem', 'dependvalue'];
        $item = (object)[
            'typ' => $this->type,
            'hasvalue' => $this->get_hasvalue(),
        ];
        foreach ($fields as $field) {
            $tag = core_text::strtoupper($field);
            if (array_key_exists($tag, $data['#'])) {
                $item->$field = $data['#'][$tag][0]['#'];
            }
        }
        if (!empty($item->dependitem) && array_key_exists($item->dependitem, $idsmap)) {
            $item->dependitem = $idsmap[$item->dependitem];
        } else {
            unset($item->dependitem);
            unset($item->dependvalue);
        }
        if (!isset($item->presentation)) {
            $item->presentation = '';
        }
        return $item;
    }

    /**
     * Imports a feedback item
     *
     * @param array $data item data from the XML file, result of xmlize()
     * @param stdClass $feedback
     * @param int $version version of the export file
     * @param array $idsmap map of item ids in the export file and in the current feedback (for restoring dependencies)
     * @return bool|int id of the new feedback item or false if nothing imported
     */
    public function import($data, $feedback, $version, $idsmap) {
        global $DB;
        if (!$item = $this->prepare_for_import($data, $feedback, $version, $idsmap)) {
            return false;
        }

        $lastposition = $DB->count_records('feedback_item', array('feedback' => $feedback->id));
        $item->position = $lastposition + 1;
        $item->feedback = $feedback->id;
        $item->template = 0;
        return $DB->insert_record('feedback_item', $item);
    }
}
