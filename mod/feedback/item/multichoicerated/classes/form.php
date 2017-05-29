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

class feedbackitem_multichoicerated_form extends mod_feedback_item_form {

    public function definition() {
        $item = $this->_customdata['item'];
        $nameoptions = $this->_customdata['nameoptions'];

        $mform =& $this->_form;

        $mform->addElement('header', 'general',
                get_string('pluginname', 'feedbackitem_' . $this->type));

        $mform->addElement('advcheckbox', 'required', get_string('required', 'feedback'), '' , null , array(0, 1));

        $mform->addElement('editor', 'name_editor', get_string('item_name', 'feedback'), null, $nameoptions);

        $mform->addElement('text',
                            'label',
                            get_string('item_label', 'feedback'),
                            array('size'=>FEEDBACK_ITEM_LABEL_TEXTBOX_SIZE,
                                  'maxlength'=>255));

        $mform->addElement('select',
                            'subtype',
                            get_string('multichoicetype', 'feedbackitem_multichoicerated'),
                            array('r'=>get_string('radio', 'feedback'),
                                  'd'=>get_string('dropdown', 'feedback')));

        $mform->addElement('select',
                            'horizontal',
                            get_string('adjustment', 'feedback').'&nbsp;',
                            array(0 => get_string('vertical', 'feedback'),
                                  1 => get_string('horizontal', 'feedback')));
        $mform->disabledIf('horizontal', 'subtype', 'eq', 'd');

        $mform->addElement('selectyesno',
                           'hidenoselect',
                           get_string('hide_no_select_option', 'feedback'));
        $mform->disabledIf('hidenoselect', 'subtype', 'eq', 'd');

        $mform->addElement('selectyesno',
                           'ignoreempty',
                           get_string('do_not_analyse_empty_submits', 'feedback'));
        $mform->disabledIf('ignoreempty', 'required', 'eq', '1');

        $this->values = $mform->addElement('textarea',
                            'values',
                            get_string('multichoice_values', 'feedbackitem_multichoicerated'),
                            'wrap="virtual" rows="10" cols="65"');

        $mform->addElement('static',
                            'hint',
                            '',
                            get_string('use_one_line_for_each_value', 'feedback'));

        parent::definition();
        $this->set_data($item);

    }

    public function set_data($item) {
        $info = $this->_customdata['info'];

        $item->horizontal = $info->horizontal;

        $item->subtype = $info->subtype;

        $item->values = $info->values;

        return parent::set_data($item);
    }

    public function get_data() {
        if (!$item = parent::get_data()) {
            return false;
        }

        $itemobj = new feedbackitem_multichoicerated_plugin();

        $presentation = $itemobj->prepare_presentation_values_save(trim($item->values),
                                                FEEDBACK_MULTICHOICERATED_VALUE_SEP2,
                                                FEEDBACK_MULTICHOICERATED_VALUE_SEP);
        if (!isset($item->subtype)) {
            $subtype = 'r';
        } else {
            $subtype = substr($item->subtype, 0, 1);
        }
        if (isset($item->horizontal) AND $item->horizontal == 1 AND $subtype != 'd') {
            $presentation .= FEEDBACK_MULTICHOICERATED_ADJUST_SEP.'1';
        }
        $item->presentation = $subtype.FEEDBACK_MULTICHOICERATED_TYPE_SEP.$presentation;
        if (!isset($item->hidenoselect)) {
            $item->hidenoselect = 1;
        }
        if (!isset($item->ignoreempty)) {
            $item->ignoreempty = 0;
        }
        return $item;
    }
}
