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
 * File types and type groups selection form element.
 *
 * @package   core_form
 * @copyright 2016 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot.'/lib/form/group.php');

/**
 * Class for a custom form element to select file types and type groups.
 *
 * @package   core_form
 * @category  form
 * @copyright 2016 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_filetypes extends MoodleQuickForm_group {

    /**
     * @var array The system selected file type choices.
     */
    private $filetypes = [];

    /**
     * @var core_form\filetypes  Provides information about the type and group options.
     */
    private $typeinfo;

    /**
     * @var bool Allow selection of 'All file types' (will be stored as '*').
     */
    private $allowall = true;

    /**
     * Constructor
     *
     * @param string $elementName Element's name
     * @param mixed $elementLabel Label(s) for an element
     * @param mixed $options element options:
     *   'filetypes': Set of filetypes to choose from as an array, default - no restriction; example ['filetypes' => ['web_image']]
     *   'allowall': Allow to select 'All file types', default - true when no filetypes specified. When filetypes are specified it is N/A.
     * @param mixed $attributes Either a typical HTML attribute string or an associative array
     */
    public function __construct($elementName = null, $elementLabel = null, $options = null, $attributes = null) {
        parent::__construct($elementName, $elementLabel, null, '', true);
        $this->_persistantFreeze = true;
        $this->_type = 'filetypes';

        $this->setAttributes(array('name' => $elementName));    // Necessary when frozen.
        $this->updateAttributes($attributes);

        if (is_array($options) && $options) {
            if (array_key_exists('filetypes', $options) && is_array($options['filetypes'])) {
                $this->filetypes = $options['filetypes'];
            }
            if (!$this->filetypes && array_key_exists('allowall', $options)) {
                $this->allowall = (bool)$options['allowall'];
            }
        }
    }

    /**
     * Retrieves instance of filetypes class
     * @return \core_form\filetypes
     */
    private function get_typeinfo() {
        if ($this->typeinfo === null) {
            $this->typeinfo = new core_form\filetypes($this->filetypes, $this->allowall);
        }
        return $this->typeinfo;
    }

    /**
     * Assemble the elements of the form control.
     */
    public function _createElements() {
        $this->_generateId();

        $this->_elements = array();

        $this->_elements['label'] = $this->createFormElement('static', 'label', null, '');
        $this->_elements['choose'] = $this->createFormElement('button', 'choose',
            get_string('choosetypes', 'form'),
            array(
                'id' => $this->getAttribute('id') . '_choose',
                'class' => 'form-filetypes-choose',
            )
        );
        $this->_elements['value'] = $this->createFormElement('text', 'value', '',
            array(
                'id' => $this->getAttribute('id'),
                'class' => 'form-filetypes-value',
            )
        );

        foreach ($this->_elements as $element) {
            if (method_exists($element, 'setHiddenLabel')) {
                $element->setHiddenLabel(true);
            }
        }
    }

    /**
     * Return the valid selected groups or file types.
     *
     * @param array $submitValues submitted values
     * @param bool $assoc if true the retured value is associated array
     * @return array
     */
    public function exportValue(&$submitValues, $assoc = false) {
        $typeinfo = $this->get_typeinfo();
        $typegroups = $typeinfo->get_typegroups();
        $alltypes = $typeinfo->get_alltypes();

        $value = array();
        $formval = $this->_elements['value']->exportValue($submitValues[$this->getName()], false);
        if ($formval) {
            $types = preg_split('/\s*,\s*/', trim(strtolower($formval)), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($types as $type) {
                // Return only the groups and types we know of.
                if ($type === '*' && $this->allowall) {
                    // "All file types" selected, ignore any other selection.
                    $value = ['*'];
                    break;
                } else if (isset($alltypes[$type])) {
                    $value[] = $type;
                } else if (isset($typegroups[$type])) {
                    if (!empty($typegroups[$type]->isoption)) {
                        $value[] = $type;
                    }
                }
            }
        }

        $value = implode(',', $value);
        return $this->_prepareValue($value, $assoc);
    }

    /**
     * Accepts a renderer
     *
     * @param HTML_QuickForm_Renderer $renderer An HTML_QuickForm_Renderer object
     * @param bool $required Whether a group is required
     * @param string $error An error message associated with a group
     */
    public function accept(&$renderer, $required = false, $error = null) {
        global $PAGE;

        $PAGE->requires->strings_for_js(array('savechoices', 'noselection'), 'form');
        $PAGE->requires->string_for_js('cancel', 'moodle');
        $PAGE->requires->js_call_amd(
            'core/form-filetypes',
            'initialise',
            array(
                $this->getAttribute('id'),
                $this->getLabel(),
                $this->filetypes,
                $this->allowall
            )
        );
        if ($this->_flagFrozen) {
            // Don't render the choose button if the control is frozen.
            unset($this->_elements['choose']);
        }
        parent::accept($renderer, $required, $error);
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event Name of event
     * @param mixed $arg event arguments
     * @param object $caller calling object
     * @return bool
     */
    public function onQuickFormEvent($event, $arg, &$caller) {
        switch ($event) {
            case 'updateValue':
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value) {
                    if ($caller->isSubmitted()) {
                        $value = $this->_findValue($caller->_submitValues);
                    } else {
                        $value = (string)$this->_findValue($caller->_defaultValues);
                    }
                }
                if (!is_array($value)) {
                    $value = array('value' => $value);
                }
                if ($value['value'] !== null) {
                    $value['label'] = $this->render_label($value['value']);
                }
                $this->setValue($value);
                return true;
        }
        return parent::onQuickFormEvent($event, $arg, $caller);
    }

    /**
     * Generate the display label contents.
     *
     * @param string $value  the ;-delimited value set.
     * @return string  the HTML markup.
     */
    private function render_label($value) {
        global $OUTPUT;

        $typeinfo = $this->get_typeinfo();
        $typegroups = $typeinfo->get_typegroups();
        $alltypes = $typeinfo->get_alltypes();
        $tplcontext = array('items' => array());

        $types = preg_split('/\s*,\s*/', trim(strtolower($value)), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($types as $val) {
            if (isset($alltypes[$val])) {
                $tplcontext['items'][] = $alltypes[$val];
            } else if (isset($typegroups[$val])) {
                $tplcontext['items'][] = $typegroups[$val];
            }
        }

        $template = $OUTPUT->render_from_template('core/form_filetypes_label', $tplcontext);
        return html_writer::div($template, 'form-filetypes',
            array('id' => $this->getAttribute('id') . '_label'));
    }
}
