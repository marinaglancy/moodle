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
 * Contains class mod_feedback_complete_form
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_feedback_complete_form
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_complete_form extends moodleform {

    const MODE_COMPLETE = 1;
    const MODE_PRINT = 2;
    const MODE_EDIT = 3;
    const MODE_VIEW_RESPONSE = 4;
    const MODE_VIEW_TEMPLATE = 5;

    protected $mode;
    /** @var mod_feedback_structure|mod_feedback_completion */
    protected $structure;
    /** @var mod_feedback_completion */
    protected $completion;

    //protected $feedback;
    /** @var cm_info */
    //protected $cm;
    //protected $courseid;
    //protected $templateid;

    protected $gopage;
    //protected $completedtmp;
    //protected $completed;
    protected $hasrequired = false;
    //protected $isempty = true;
    //protected $allitems = null;
    //protected $valuestmp = null;
    //protected $values = null;

    public function __construct($mode, mod_feedback_structure $structure, $id, $customdata = null) {
        $this->mode = $mode;
        $this->structure = $structure;
        $this->gopage = isset($customdata['gopage']) ?
                $customdata['gopage'] : 0;
        $isanonymous = $this->structure->is_anonymous() ? ' ianonymous' : '';
        parent::__construct(null, $customdata, 'POST', '',
                array('id' => $id, 'class' => 'feedback-form' . $isanonymous), true);
    }

    public function definition() {
        global $DB;

        $mform = $this->_form;
        $mform->addElement('hidden', 'id', $this->get_cm()->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $this->get_current_course_id());
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'gopage');
        $mform->setType('gopage', PARAM_INT);
        $mform->addElement('hidden', 'lastpage');
        $mform->setType('lastpage', PARAM_INT);
        $mform->addElement('hidden', 'startitempos');
        $mform->setType('startitempos', PARAM_INT);
        $mform->addElement('hidden', 'lastitempos');
        $mform->setType('lastitempos', PARAM_INT);

        $feedback = $this->get_feedback();
        if ($this->structure->is_anonymous()) {
            $anonymousmodeinfo = get_string('anonymous', 'feedback');
        } else {
            $anonymousmodeinfo = get_string('non_anonymous', 'feedback');
        }
        if (isloggedin() && !isguestuser() && $this->mode != self::MODE_EDIT && $this->mode != self::MODE_VIEW_TEMPLATE &&
                    $this->mode != self::MODE_VIEW_RESPONSE) {
            $element = $mform->addElement('static', 'anonymousmode', '',
                    get_string('mode', 'feedback') . ': ' . $anonymousmodeinfo);
            $element->setAttributes($element->getAttributes() + ['class' => 'feedback_mode']);
        }

        // Add buttons to go to previous/next pages and submit the feedback.
        if ($this->mode == self::MODE_COMPLETE) {
            $buttonarray = array();
            $buttonarray[] = &$mform->createElement('submit', 'gopreviouspage', get_string('previous_page', 'feedback'));
            $buttonarray[] = &$mform->createElement('submit', 'gonextpage', get_string('next_page', 'feedback'),
                    array('class' => 'form-submit'));
            $buttonarray[] = &$mform->createElement('submit', 'savevalues', get_string('save_entries', 'feedback'),
                    array('class' => 'form-submit'));
            $buttonarray[] = &$mform->createElement('static', 'buttonsseparator', '', '<br>');
            $buttonarray[] = &$mform->createElement('cancel');
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        }

        // Set data.
        $this->set_data(array('gopage' => $this->gopage));
    }

    /**
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     */
    public function definition_after_data() {
        parent::definition_after_data();

        if ($this->mode == self::MODE_COMPLETE) {
            $this->definition_after_data_complete();
        } else {
            $this->definition_after_data_preview();
        }
    }

    protected function definition_after_data_complete() {
        if (!$this->structure instanceof mod_feedback_completion) {
            // We should not really be here but just in case.
            return;
        }
        $pages = $this->structure->get_pages();
        $gopage = $this->gopage;
        $pageitems = $pages[$gopage];
        $hasnextpage = $gopage < count($pages) - 1; // Until we complete this page we can not trust get_next_page().
        $hasprevpage = $gopage && ($this->structure->get_previous_page($gopage, false) !== null);

        // Add elements.
        foreach ($pageitems as $item) {
            $itemobj = feedback_get_item_class($item->typ);
            $itemobj->complete_form_element($item, $this);
        }

        // Remove invalid buttons (for example, no "previous page" if we are on the first page).
        if (!$hasprevpage) {
            $this->remove_button('gopreviouspage');
        }
        if (!$hasnextpage) {
            $this->remove_button('gonextpage');
        }
        if ($hasnextpage) {
            $this->remove_button('savevalues');
        }
    }

    protected function definition_after_data_preview() {
        foreach ($this->structure->get_items() as $feedbackitem) {
            $itemobj = feedback_get_item_class($feedbackitem->typ);
            $itemobj->complete_form_element($feedbackitem, $this);
        }
    }

    private function remove_button($buttonname) {
        $el = $this->_form->getElement('buttonar');
        foreach ($el->_elements as $idx => $button) {
            if ($button instanceof MoodleQuickForm_submit && $button->getName() === $buttonname) {
                unset($el->_elements[$idx]);
                return;
            }
        }
    }

    /**
     * Returns value for this element that is already stored in temporary or permanent table,
     * usually only available when user clicked "Previous page". Null means no value is stored.
     *
     * @param stdClass $item
     * @return string
     */
    public function get_item_value($item) {
        if ($this->structure instanceof mod_feedback_completion) {
            if ($this->mode == self::MODE_COMPLETE) {
                return $this->structure->get_values_tmp($item);
            } else if ($this->mode == self::MODE_VIEW_RESPONSE) {
                return $this->structure->get_values($item);
            }
        }
        return null;
    }

    public function get_course_id() {
        return $this->structure->get_courseid();
    }

    public function get_feedback() {
        return $this->structure->get_feedback();
    }

    public function get_mode() {
        return $this->mode;
    }

    public function is_frozen() {
        return $this->mode == self::MODE_VIEW_RESPONSE;
    }

    public function get_suggested_class($item) {
        $class = "feedback_itemlist feedback-item-{$item->typ}";
        if ($item->dependitem) {
            $class .= " feedback_depend";
        }
        if ($item->typ !== 'pagebreak') {
            $itemobj = feedback_get_item_class($item->typ);
            if ($itemobj->get_hasvalue()) {
                $class .= " feedback_hasvalue";
            }
        }
        return $class;
    }

    /**
     * @param stdClass $item
     * @param HTML_QuickForm_element|array $element
     * @return HTML_QuickForm_element
     */
    public function add_form_element($item, $element, $addrequiredrule = true, $setdefaultvalue = true) {
        global $OUTPUT;
        // Add element to the form.
        if (is_array($element)) {
            if ($this->is_frozen() && $element[0] === 'text') {
                // Convert 'text' element to 'static' when freezing for better display.
                $element = ['static', $element[1], $element[2]];
            }
            $element = call_user_func_array(array($this->_form, 'createElement'), $element);
        }
        $element = $this->_form->addElement($element);

        // Prepend standard CSS classes to the element classes.
        $attributes = $element->getAttributes();
        $class = !empty($attributes['class']) ? ' ' . $attributes['class'] : '';
        $attributes['class'] = $this->get_suggested_class($item) . $class;
        $element->setAttributes($attributes);

        // Add required rule.
        if ($item->required && $addrequiredrule) {
            $this->_form->addRule($element->getName(), get_string('required'), 'required', null, 'client');
        }

        // Set default value.
        if ($setdefaultvalue && ($tmpvalue = $this->get_item_value($item))) {
            $this->_form->setDefault($element->getName(), $tmpvalue);
        }

        // Freeze if needed.
        if ($this->is_frozen()) {
            $element->freeze();
        }

        // Add red asterisks on required fields.
        if ($item->required) {
            $required = '<img class="req" title="'.get_string('requiredelement', 'form').'" alt="'.
                    get_string('requiredelement', 'form').'" src="'.$OUTPUT->pix_url('req') .'" />';
            $element->setLabel($element->getLabel() . $required);
            $this->hasrequired = true;
        }

        // Add different useful stuff to the question name.
        $this->add_item_label($item, $element);
        $this->add_item_dependencies($item, $element);
        $this->add_item_number($item, $element);

        if ($this->mode == self::MODE_EDIT) {
            $this->enhance_name_for_edit($item, $element);
        }

        return $element;
    }

    protected function add_item_number($item, $element) {
        if ($this->get_feedback()->autonumbering && !empty($item->itemnr)) {
            $name = $element->getLabel();
            $element->setLabel(html_writer::span($item->itemnr. '.', 'itemnr') . ' ' . $name);
        }
    }

    protected function add_item_label($item, $element) {
        if (strlen($item->label) && ($this->mode == self::MODE_EDIT || $this->mode == self::MODE_VIEW_TEMPLATE)) {
            $name = $element->getLabel();
            $name = '('.format_string($item->label).') '.$name;
            $element->setLabel($name);
        }
    }

    protected function add_item_dependencies($item, $element) {
        global $DB;
        $allitems = $this->structure->get_items();
        if ($item->dependitem && ($this->mode == self::MODE_EDIT || $this->mode == self::MODE_VIEW_TEMPLATE)) {
            if (isset($allitems[$item->dependitem])) {
                $dependitem = $allitems[$item->dependitem];
                $name = $element->getLabel();
                $name .= html_writer::span(' ('.format_string($dependitem->label).'-&gt;'.$item->dependvalue.')',
                        'feedback_depend');
                $element->setLabel($name);
            }
        }
    }

    /**
     *
     * @param HTML_QuickForm_element $element
     */
    protected function guess_element_id($item, $element) {
        if (!$id = $element->getAttribute('id')) {
            $attributes = $element->getAttributes();
            $id = $attributes['id'] = 'feedback_item_' . $item->id;
            $element->setAttributes($attributes);
        }
        if ($element->getType() === 'group') {
            return 'fgroup_' . $id;
        }
        return 'fitem_' . $id;
    }

    protected function pagebreak_actions($item) {
        $actions = array();
        $strdelete = get_string('delete_pagebreak', 'feedback');
        $actions['delete'] = new action_menu_link_secondary(
            new moodle_url('/mod/feedback/delete_item.php', array('deleteitem' => $item->id)),
            new pix_icon('t/delete', $strdelete, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $strdelete,
            array('class' => 'editing_delete', 'data-action' => 'delete')
        );
        return $actions;
    }

    protected function enhance_name_for_edit($item, $element) {
        global $OUTPUT, $DB;
        $menu = new action_menu();
        $menu->set_owner_selector('#' . $this->guess_element_id($item, $element));
        $menu->set_constraint('.course-content');
        $menu->set_alignment(action_menu::TR, action_menu::BR);
        $menu->set_menu_trigger(get_string('edit'));
        $menu->do_not_enhance(); // TODO remove?
        //$menu->attributes['class'] .= ' section-cm-edit-actions commands';
        $menu->prioritise = true;

        if ($item->typ === 'pagebreak') {
            $menu->do_not_enhance();
            $actions = $this->pagebreak_actions($item);
        } else {
            $itemobj = feedback_get_item_class($item->typ);
            $actions = $itemobj->edit_actions($item, $this->get_feedback(), $this->get_cm());
        }
        foreach ($actions as $action) {
            $menu->add($action);
        }
        $editmenu = $OUTPUT->render($menu);

        $name = $element->getLabel();

        $name = html_writer::span('', 'itemdd', array('id' => 'feedback_item_box_' . $item->id)) .
                html_writer::span($name, 'itemname') .
                html_writer::span($editmenu, 'itemactions');
        $element->setLabel(html_writer::span($name, 'itemtitle'));
    }

    public function add_form_group_element($item, $groupinputname, $name, $elements, $separator,
            $class = '') {
        $objects = array();
        foreach ($elements as $element) {
            $object = call_user_func_array(array($this->_form, 'createElement'), $element);
            //$object->setAttributes($object->getAttributes() + array('class' => 'subitem'));
            $objects[] = $object;
        }
        $element = $this->add_form_element($item,
                ['group', $groupinputname, $name, $objects, $separator, false],
                false,
                false);
        if ($class !== '') {
            $attributes = $element->getAttributes();
            $attributes['class'] .= ' ' . $class;
            $element->setAttributes($attributes);
        }
        return $element;
    }

    public function set_element_default($elementname, $defaultvalue) {
        if ($elementname instanceof HTML_QuickForm_element) {
            $elementname = $elementname->getName();
        }
        $this->_form->setDefault($elementname, $defaultvalue);
    }

    public function set_element_type($elementname, $type) {
        if ($elementname instanceof HTML_QuickForm_element) {
            $elementname = $elementname->getName();
        }
        $this->_form->setType($elementname, $type);
    }

    /**
     * Adds a validation rule for the given field
     *
     * Wrapper for $this->_form->addRule()
     *
     * @param string $element Form element name
     * @param string $message Message to display for invalid data
     * @param string $type Rule type, use getRegisteredRules() to get types
     * @param string $format (optional)Required for extra rule data
     * @param string $validation (optional)Where to perform validation: "server", "client"
     * @param bool $reset Client-side validation: reset the form element to its original value if there is an error?
     * @param bool $force Force the rule to be applied, even if the target form element does not exist
     */
    public function add_element_rule($element, $message, $type, $format = null, $validation = 'server',
            $reset = false, $force = false) {
        if ($element instanceof HTML_QuickForm_element) {
            $element = $element->getName();
        }
        $this->_form->addRule($element, $message, $type, $format, $validation, $reset, $force);
    }

    public function add_validation_rule(callable $callback) {
        if ($this->mode == self::MODE_COMPLETE) {
            $this->_form->addFormRule($callback);
        }
    }

    /**
     * Returns a reference to the element
     *
     * Wrapper for funciton $this->_form->getElement()
     *
     * @param string $elementname Element name
     * @return HTML_QuickForm_element reference to element
     */
    public function get_form_element($elementname) {
        return $this->_form->getElement($elementname);
    }

    /**
     *
     * @return cm_info
     */
    public function get_cm() {
        return $this->structure->get_cm();
    }

    public function get_current_course_id() {
        return $this->structure->get_courseid() ?: $this->get_feedback()->course;
    }

    public function display() {
        global $OUTPUT;
        // Finalize the form definition if not yet done.
        if (!$this->_definition_finalized) {
            $this->_definition_finalized = true;
            $this->definition_after_data();
        }

        $mform = $this->_form;

        // Add "This form has required fields" text in the bottom of the form.
        if (($mform->_required || $this->hasrequired) &&
                ($this->mode == self::MODE_COMPLETE || $this->mode == self::MODE_PRINT || $this->mode == self::MODE_VIEW_TEMPLATE)) {
            $element = $mform->addElement('static', 'requiredfields', '',
                    get_string('somefieldsrequired', 'form',
                            '<img alt="'.get_string('requiredelement', 'form').'" src="'.$OUTPUT->pix_url('req') .'" />'));
            $element->setAttributes($element->getAttributes() + ['class' => 'requirednote']);
        }

        // Reset _required array so the default red * are not displayed.
        $mform->_required = array();

        // Move buttons to the end of the form.
        if ($this->mode == self::MODE_COMPLETE) {
            $mform->addElement('hidden', '__dummyelement');
            $buttons = $mform->removeElement('buttonar', false);
            $mform->insertElementBefore($buttons, '__dummyelement');
            $mform->removeElement('__dummyelement');
        }

        $this->_form->display();
    }
}
