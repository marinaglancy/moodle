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

    protected $mode;
    protected $feedback;
    /** @var cm_info */
    protected $cm;
    protected $courseid;
    protected $gopage;
    protected $completedtmp;
    protected $completed;
    protected $hasrequired = false;

    public function __construct($mode, $id, $customdata = null) {
        $this->mode = $mode;
        $isanonymous = $customdata['feedback']->anonymous == FEEDBACK_ANONYMOUS_YES ?
                ' ianonymous' : '';
        parent::__construct(null, $customdata, 'POST', '',
                array('id' => $id, 'class' => 'feedback-form' . $isanonymous), true);
    }

    public function definition() {
        $this->feedback = $this->_customdata['feedback'];
        $this->cm = $this->_customdata['cm'];
        $this->courseid = !empty($this->_customdata['courseid']) ?
                $this->_customdata['courseid'] : $this->cm->course;
        $this->gopage = isset($this->_customdata['gopage']) ?
                $this->_customdata['gopage'] : 0;

        $mform = $this->_form;
        $mform->addElement('hidden', 'id', $this->cm->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $this->courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'gopage');
        $mform->setType('gopage', PARAM_INT);
        $mform->addElement('hidden', 'lastpage');
        $mform->setType('lastpage', PARAM_INT);
        $mform->addElement('hidden', 'startitempos');
        $mform->setType('startitempos', PARAM_INT);
        $mform->addElement('hidden', 'lastitempos');
        $mform->setType('lastitempos', PARAM_INT);

        if ($this->feedback->anonymous == FEEDBACK_ANONYMOUS_YES) {
            $anonymousmodeinfo = get_string('anonymous', 'feedback');
        } else if ($this->feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
            $anonymousmodeinfo = get_string('non_anonymous', 'feedback');
        }
        if (isloggedin() && !isguestuser() && $this->mode != self::MODE_EDIT) {
            $element = $mform->addElement('static', 'anonymousmode', '',
                    get_string('mode', 'feedback') . ': ' . $anonymousmodeinfo);
            $element->setAttributes($element->getAttributes() + ['class' => 'feedback_mode']);
        }

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

        if ($this->mode == self::MODE_COMPLETE) {
            $this->completedtmp = feedback_retrieve_response_tmp($this->feedback, $this->courseid);
        } else {
            $this->completed = isset($this->_customdata['completed']) ?
                    $this->_customdata['completed'] : array();
        }

        $this->set_data(array('gopage' => $this->gopage));
    }

    /*
    public function set_defaults() {
        // TODO this is dodgy
        global  $DB;
        $defaultvalues = array();
        if ($this->completedtmp) {
            $sql = "SELECT fi.id, fi.typ, fv.value
                       FROM {feedback_valuetmp} fv, {feedback_item} fi
                      WHERE fv.course_id = :courseid
                            AND fv.completed = :completedid
                            AND fv.item = fi.id";
            $params['completedid'] = $this->completedtmp->id;
            $params['courseid']    = $this->courseid;
            $params['feedbackid']  = $this->feedback->id;

            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $record) {
                $defaultvalues[$record->typ . '_' . $record->id] = $record->value;
            }
            $rs->close();
        }
    }
     */

    //public function get_completedtmp_id() {
    //    return isset($this->completedtmp->id) ? $this->completedtmp->id : null;
    //}

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
        global $DB, $OUTPUT;
        $mform = $this->_form;
        list($startposition, $firstpagebreak, $ispagebreak, $feedbackitems) =
                feedback_get_page_boundaries($this->feedback, $this->gopage);

        // Add elements.
        $startitem = null;
        $lastbreakposition = 0;
        foreach ($feedbackitems as $feedbackitem) {
            if (!isset($startitem)) {
                // Avoid showing double pagebreaks.
                if ($feedbackitem->typ == 'pagebreak') {
                    continue;
                }
                $startitem = $feedbackitem;
            }

            if ($feedbackitem->dependitem > 0) {
                // Check if the conditions are ok.
                if (!isset($this->completedtmp->id) OR
                        !feedback_compare_item_value($this->completedtmp->id,
                            $feedbackitem->dependitem, $feedbackitem->dependvalue, true)) {
                    $lastitem = $feedbackitem;
                    $lastbreakposition = $feedbackitem->position;
                    continue;
                }
            }

            if ($feedbackitem->typ != 'pagebreak') {
                $itemobj = feedback_get_item_class($feedbackitem->typ);
                $itemobj->complete_form_element($feedbackitem, $this);
            }

            $lastbreakposition = $feedbackitem->position; // Last item-pos (item or pagebreak).
            if ($feedbackitem->typ == 'pagebreak') {
                break;
            } else {
                $lastitem = $feedbackitem;
            }
        }

        // Remove invalid buttons (for example, no "previous page" if we are on the first page).
        $maxitemcount = $DB->count_records('feedback_item', array('feedback' => $this->feedback->id));
        if (!$ispagebreak || $lastbreakposition <= $firstpagebreak->position) {
            $this->remove_button('gopreviouspage');
        }
        if ($lastbreakposition >= $maxitemcount) {
            $this->remove_button('gonextpage');
        }
        if ($lastbreakposition < $maxitemcount) {
            $this->remove_button('savevalues');
        }
    }

    protected function definition_after_data_preview() {
        global $DB;
        $mform = $this->_form;
        $feedbackitems = $DB->get_records('feedback_item', array('feedback'=>$this->feedback->id), 'position');
        $pageidx = 1;
        /*foreach ($feedbackitems as $feedbackitem) {
            if ($feedbackitem->typ === 'pagebreak') {
                $mform->addElement('header', 'page'.$pageidx, 'PAGE '.$pageidx); // TODO string
                $mform->setExpanded('page'.$pageidx);
                $pageidx++;
                break;
            }
        }*/
        foreach ($feedbackitems as $feedbackitem) {
            if ($feedbackitem->typ !== 'pagebreak') {
                $itemobj = feedback_get_item_class($feedbackitem->typ);
                $itemobj->complete_form_element($feedbackitem, $this);
            } else {
                $this->add_form_element($feedbackitem,
                        ['static', 'page'.$pageidx, '', '<hr class="feedback_pagebreak">']);
                //$element = $mform->addElement('static', 'page'.$pageidx, '', '<hr class="feedback_pagebreak">');
                //$element->setAttributes($element->getAttributes() + array('class' => 'feedback-item-pagebreak'));
                //$mform->addElement('header', 'page'.$pageidx, 'PAGE '.$pageidx); // TODO string
                //$mform->setExpanded('page'.$pageidx);
                $pageidx++;
            }

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
     * Returns value for this element that is already stored in temporary table,
     * usually only available when user clicked "Previous page". Null means no value is stored.
     *
     * @param stdClass $feedbackitem
     * @return string
     */
    public function get_item_value($feedbackitem) {
                $value = null;
                //get the value
                //$frmvaluename = $feedbackitem->typ . '_'. $feedbackitem->id;
                /*if ($mform->getElementValue('id')) {
                    $value = $mform->getElementValue($frmvaluename);
                    $value = feedback_clean_input_value($feedbackitem, $value);
                } else {*/
                    if (isset($this->completedtmp->id)) {
                        $value = feedback_get_item_value($this->completedtmp->id,
                                                         $feedbackitem->id,
                                                         true);
                    } else if (isset($this->completed->id)) {
                        $value = feedback_get_item_value($this->completed->id,
                                                         $feedbackitem->id,
                                                         false);
                    }
                //}
        return $value;
    }

    public function get_course_id() {
        return $this->courseid;
    }

    public function get_feedback() {
        return $this->feedback;
    }

    public function get_mode() {
        return $this->mode;
    }

    public function is_frozen() {
        return $this->mode == self::MODE_VIEW_RESPONSE;
    }

    public function get_suggested_class($item) {
        $class = "feedback-item-{$item->typ}";
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

        //$element->setLabel($itemclass->get_display_name()); // TODO do I want it?

        // Add red asterisks on required fields.
        if ($item->required) {
            $required = '<img class="req" title="'.get_string('requiredelement', 'form').'" alt="'.
                    get_string('requiredelement', 'form').'" src="'.$OUTPUT->pix_url('req') .'" />';
            $element->setLabel($element->getLabel() . $required);
            $this->hasrequired = true;
        }

        if ($this->mode == self::MODE_EDIT) {
            $this->enhance_name_for_edit($item, $element);
        }

        return $element;
    }

    /**
     *
     * @param HTML_QuickForm_element $element
     */
    protected function guess_element_id($element) {
        $element->_generateId();
        if ($element->getType() === 'group') {
            return 'fgroup_' . $element->getAttribute('id');
        }
        return 'fitem_' . $element->getAttribute('id');
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
        global $OUTPUT;
        $menu = new action_menu();
        $menu->set_owner_selector('#' . $this->guess_element_id($element));
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
            $actions = $itemobj->edit_actions($item, $this->feedback, $this->cm);
        }
        foreach ($actions as $action) {
            $menu->add($action);
        }
        $editmenu = $OUTPUT->render($menu);

        $name = $element->getLabel();
        $name = html_writer::span($name, 'itemname') .
                html_writer::span($editmenu, 'itemactions');
        $element->setLabel(html_writer::span($name, 'itemtitle'));
    }

    public function add_form_group_element($item, $groupinputname, $name, $elements, $separator,
            $class = '') {
        $objects = array();
        foreach ($elements as $element) {
            $objects[] = call_user_func_array(array($this->_form, 'createElement'), $element);
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
        return $this->cm;
    }

    public function get_current_course_id() {
        if ($this->feedback->course == SITEID && $this->courseid) {
            return $this->courseid;
        }
        return $this->feedback->course;
    }

    public function display() {
        global $OUTPUT;
        // Finalize the form definition if not yet done.
        if (!$this->_definition_finalized) {
            $this->_definition_finalized = true;
            $this->definition_after_data();
        }

        $mform = $this->_form;

        // Add "has required fields" note.
        if (($mform->_required || $this->hasrequired) &&
                ($this->mode == self::MODE_COMPLETE || $this->mode == self::MODE_PRINT)) {
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
