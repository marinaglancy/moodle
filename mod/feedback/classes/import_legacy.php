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
 * Contains class mod_feedback_structure
 *
 * @package   mod_feedback
 * @copyright 2017 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Stores and manipulates the structure of the feedback or template (items, pages, etc.)
 *
 * @package   mod_feedback
 * @copyright 2017 Marina Glancy
 * @author    Andreas Grabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_feedback_import_legacy {

    /** @var mod_feedback_structure */
    protected $feedbackstructure;

    /**
     * Constructor.
     * @param mod_feedback_structure $feedbackstructure
     */
    public function __construct($feedbackstructure) {
        $this->feedbackstructure = $feedbackstructure;
    }

    /**
     * Parses contents of XML file and returns array of items
     *
     * @param string $xmlcontent
     * @return array|bool array of items or false if parsing was unsuccessful
     */
    protected function feedback_load_xml_data($xmlcontent) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/xmlize.php');

        if (!$xmlcontent = $this->feedback_check_xml_utf8($xmlcontent)) {
            return false;
        }

        $data = xmlize($xmlcontent, 1, 'UTF-8');

        if (intval($data['FEEDBACK']['@']['VERSION']) != 200701) {
            return false;
        }
        $data = $data['FEEDBACK']['#']['ITEMS'][0]['#']['ITEM'];
        return $data;
    }

    /**
     * Imports items from XML file in 200701 format
     *
     * @param string $xmlcontent
     * @param bool $deleteolditems
     * @return bool
     */
    public function import($xmlcontent, $deleteolditems) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/mod/feedback/lib.php');

        if (!$data = $this->feedback_load_xml_data($xmlcontent)) {
            return false;
        }

        $feedbackid = $this->feedbackstructure->get_feedback()->id;
        feedback_load_feedback_items();

        if (!is_array($data)) {
            \core\notification::add(get_string('data_is_not_an_array', 'feedback'), \core\output\notification::NOTIFY_ERROR);
            return false;
        }

        if ($deleteolditems) {
            feedback_delete_all_items($feedbackid);
            $position = 0;
        } else {
            //items will be add to the end of the existing items
            $position = $DB->count_records('feedback_item', array('feedback'=>$feedbackid));
        }

        //depend items we are storing temporary in an mapping list array(new id => dependitem)
        //we also store a mapping of all items array(oldid => newid)
        $dependitemsmap = array();
        $itembackup = array();
        foreach ($data as $item) {
            $position++;
            //check the typ
            $typ = $item['@']['TYPE'];

            //check oldtypes first
            switch($typ) {
                case 'radio':
                    $typ = 'multichoice';
                    $oldtyp = 'radio';
                    break;
                case 'dropdown':
                    $typ = 'multichoice';
                    $oldtyp = 'dropdown';
                    break;
                case 'check':
                    $typ = 'multichoice';
                    $oldtyp = 'check';
                    break;
                case 'radiorated':
                    $typ = 'multichoicerated';
                    $oldtyp = 'radiorated';
                    break;
                case 'dropdownrated':
                    $typ = 'multichoicerated';
                    $oldtyp = 'dropdownrated';
                    break;
                default:
                    $oldtyp = $typ;
            }

            $itemclass = 'feedback_item_'.$typ;
            if ($typ != 'pagebreak' AND !class_exists($itemclass)) {
                \core\notification::add(get_string('typenotfound', 'feedback', $typ), \core\output\notification::NOTIFY_ERROR);
                continue;
            }
            $itemobj = new $itemclass();

            $newitem = new stdClass();
            $newitem->feedback = $feedbackid;
            $newitem->template = 0;
            $newitem->typ = $typ;
            $newitem->name = trim($item['#']['ITEMTEXT'][0]['#']);
            $newitem->nameformat = FORMAT_HTML;
            $newitem->label = trim($item['#']['ITEMLABEL'][0]['#']);
            if ($typ === 'captcha' || $typ === 'label') {
                $newitem->label = '';
                $newitem->name = '';
            }
            $newitem->options = trim($item['#']['OPTIONS'][0]['#']);
            $newitem->presentation = trim($item['#']['PRESENTATION'][0]['#']);
            //check old types of radio, check, and so on
            switch($oldtyp) {
                case 'radio':
                    $newitem->presentation = 'r>>>>>'.$newitem->presentation;
                    break;
                case 'dropdown':
                    $newitem->presentation = 'd>>>>>'.$newitem->presentation;
                    break;
                case 'check':
                    $newitem->presentation = 'c>>>>>'.$newitem->presentation;
                    break;
                case 'radiorated':
                    $newitem->presentation = 'r>>>>>'.$newitem->presentation;
                    break;
                case 'dropdownrated':
                    $newitem->presentation = 'd>>>>>'.$newitem->presentation;
                    break;
            }

            if ($typ === 'label') {
                // For labels the label text used to be in 'presentation' field and now is in 'name' field.
                $newitem->name = $newitem->presentation;
                $newitem->presentation = '';
            }

            if (isset($item['#']['DEPENDITEM'][0]['#'])) {
                $newitem->dependitem = intval($item['#']['DEPENDITEM'][0]['#']);
            } else {
                $newitem->dependitem = 0;
            }
            if (isset($item['#']['DEPENDVALUE'][0]['#'])) {
                $newitem->dependvalue = trim($item['#']['DEPENDVALUE'][0]['#']);
            } else {
                $newitem->dependvalue = '';
            }
            $olditemid = intval($item['#']['ITEMID'][0]['#']);

            if ($typ != 'pagebreak') {
                $newitem->hasvalue = $itemobj->get_hasvalue();
            } else {
                $newitem->hasvalue = 0;
            }
            $newitem->required = intval($item['@']['REQUIRED']);
            $newitem->position = $position;
            $newid = $DB->insert_record('feedback_item', $newitem);

            $itembackup[$olditemid] = $newid;
            if ($newitem->dependitem) {
                $dependitemsmap[$newid] = $newitem->dependitem;
            }

        }
        //remapping the dependency
        foreach ($dependitemsmap as $key => $dependitem) {
            $newitem = $DB->get_record('feedback_item', array('id'=>$key));
            $newitem->dependitem = $itembackup[$newitem->dependitem];
            $DB->update_record('feedback_item', $newitem);
        }

        return true;
    }

    /**
     * Checks encoding of the export file
     *
     * @param string $text
     * @return bool|string
     */
    protected function feedback_check_xml_utf8($text) {
        //find the encoding
        $searchpattern = '/^\<\?xml.+(encoding=\"([a-z0-9-]*)\").+\?\>/is';

        if (!preg_match($searchpattern, $text, $match)) {
            return false; //no xml-file
        }

        //$match[0] = \<\? xml ... \?\> (without \)
        //$match[1] = encoding="...."
        //$match[2] = ISO-8859-1 or so on
        if (isset($match[0]) AND !isset($match[1])) { //no encoding given. we assume utf-8
            return $text;
        }

        //encoding is given in $match[2]
        if (isset($match[0]) AND isset($match[1]) AND isset($match[2])) {
            $enc = $match[2];
            return core_text::convert($text, $enc);
        }
    }

}