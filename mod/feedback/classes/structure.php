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
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Stores and manipulates the structure of the feedback or template (items, pages, etc.)
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_structure {
    /** @var stdClass record from 'feedback' table.
     * Reliably has fields: id, course, timeopen, timeclose, anonymous, completionsubmit.
     * For full object or to access any other field use $this->get_feedback()
     */
    protected $feedback;
    /** @var cm_info */
    protected $cm;
    /** @var int course where the feedback is filled. For feedbacks that are NOT on the front page this is 0 */
    protected $courseid = 0;
    /** @var int */
    protected $templateid;
    /** @var array */
    protected $allitems;
    /** @var array */
    protected $allcourses;

    /**
     * Constructor
     *
     * @param stdClass $feedback feedback object, in case of the template
     *     this is the current feedback the template is accessed from
     * @param stdClass|cm_info $cm course module object corresponding to the $feedback
     *     (at least one of $feedback or $cm is required)
     * @param int $courseid current course (for site feedbacks only)
     * @param int $templateid template id if this class represents the template structure
     */
    public function __construct($feedback, $cm, $courseid = 0, $templateid = null) {
        if ((empty($feedback->id) || empty($feedback->course)) && (empty($cm->instance) || empty($cm->course))) {
            throw new coding_exception('Either $feedback or $cm must be passed to constructor');
        }
        $this->feedback = $feedback ?: (object)['id' => $cm->instance, 'course' => $cm->course];
        $this->cm = ($cm && $cm instanceof cm_info) ? $cm :
            get_fast_modinfo($this->feedback->course)->instances['feedback'][$this->feedback->id];
        $this->templateid = $templateid;
        $this->courseid = ($this->feedback->course == SITEID) ? $courseid : 0;

        if (!$feedback) {
            // If feedback object was not specified, populate object with fields required for the most of methods.
            // These fields were added to course module cache in feedback_get_coursemodule_info().
            // Full instance record can be retrieved by calling mod_feedback_structure::get_feedback().
            $customdata = ($this->cm->customdata ?: []) + ['timeopen' => 0, 'timeclose' => 0, 'anonymous' => 0];
            $this->feedback->timeopen = $customdata['timeopen'];
            $this->feedback->timeclose = $customdata['timeclose'];
            $this->feedback->anonymous = $customdata['anonymous'];
            $this->feedback->completionsubmit = empty($this->cm->customdata['customcompletionrules']['completionsubmit']) ? 0 : 1;
        }
    }

    /**
     * Current feedback
     * @return stdClass
     */
    public function get_feedback() {
        global $DB;
        if (!isset($this->feedback->publish_stats) || !isset($this->feedback->name)) {
            // Make sure the full object is retrieved.
            $this->feedback = $DB->get_record('feedback', ['id' => $this->feedback->id], '*', MUST_EXIST);
        }
        return $this->feedback;
    }

    /**
     * Current course module
     * @return stdClass
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Id of the current course (for site feedbacks only)
     * @return stdClass
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Template id
     * @return int
     */
    public function get_templateid() {
        return $this->templateid;
    }

    /**
     * Is this feedback open (check timeopen and timeclose)
     * @return bool
     */
    public function is_open() {
        $checktime = time();
        return (!$this->feedback->timeopen || $this->feedback->timeopen <= $checktime) &&
            (!$this->feedback->timeclose || $this->feedback->timeclose >= $checktime);
    }

    /**
     * Get all items in this feedback or this template
     * @param bool $hasvalueonly only count items with a value.
     * @return array of objects from feedback_item with an additional attribute 'itemnr'
     */
    public function get_items($hasvalueonly = false) {
        global $DB;
        if ($this->allitems === null) {
            if ($this->templateid) {
                $this->allitems = $DB->get_records('feedback_item', ['template' => $this->templateid], 'position');
            } else {
                $this->allitems = $DB->get_records('feedback_item', ['feedback' => $this->feedback->id], 'position');
            }
            $idx = 1;
            foreach ($this->allitems as $id => $item) {
                $this->allitems[$id]->itemnr = $item->hasvalue ? ($idx++) : null;
            }
        }
        if ($hasvalueonly && $this->allitems) {
            return array_filter($this->allitems, function($item) {
                return $item->hasvalue;
            });
        }
        return $this->allitems;
    }

    /**
     * Is the items list empty?
     * @return bool
     */
    public function is_empty() {
        $items = $this->get_items();
        $displayeditems = array_filter($items, function($item) {
            return $item->typ !== 'pagebreak';
        });
        return !$displayeditems;
    }

    /**
     * Is this feedback anonymous?
     * @return bool
     */
    public function is_anonymous() {
        return $this->feedback->anonymous == FEEDBACK_ANONYMOUS_YES;
    }

    /**
     * Returns the formatted text of the page after submit or null if it is not set
     *
     * @return string|null
     */
    public function page_after_submit() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $pageaftersubmit = $this->get_feedback()->page_after_submit;
        if (empty($pageaftersubmit)) {
            return null;
        }
        $pageaftersubmitformat = $this->get_feedback()->page_after_submitformat;

        $context = context_module::instance($this->get_cm()->id);
        $output = file_rewrite_pluginfile_urls($pageaftersubmit,
                'pluginfile.php', $context->id, 'mod_feedback', 'page_after_submit', 0);

        return format_text($output, $pageaftersubmitformat, array('overflowdiv' => true));
    }

    /**
     * Checks if current user is able to view feedback on this course.
     *
     * @return bool
     */
    public function can_view_analysis() {
        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/feedback:viewreports', $context)) {
            return true;
        }

        if (intval($this->get_feedback()->publish_stats) != 1 ||
                !has_capability('mod/feedback:viewanalysepage', $context)) {
            return false;
        }

        if (!isloggedin() || isguestuser()) {
            // There is no tracking for the guests, assume that they can view analysis if condition above is satisfied.
            return $this->feedback->course == SITEID;
        }

        return $this->is_already_submitted(true);
    }

    /**
     * check for multiple_submit = false.
     * if the feedback is global so the courseid must be given
     *
     * @param bool $anycourseid if true checks if this feedback was submitted in any course, otherwise checks $this->courseid .
     *     Applicable to frontpage feedbacks only
     * @return bool true if the feedback already is submitted otherwise false
     */
    public function is_already_submitted($anycourseid = false) {
        global $USER, $DB;

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        $params = array('userid' => $USER->id, 'feedback' => $this->feedback->id);
        if (!$anycourseid && $this->courseid) {
            $params['courseid'] = $this->courseid;
        }
        return $DB->record_exists('feedback_completed', $params);
    }

    /**
     * Check whether the feedback is mapped to the given courseid.
     */
    public function check_course_is_mapped() {
        global $DB;
        if ($this->feedback->course != SITEID) {
            return true;
        }
        if ($DB->get_records('feedback_sitecourse_map', array('feedbackid' => $this->feedback->id))) {
            $params = array('feedbackid' => $this->feedback->id, 'courseid' => $this->courseid);
            if (!$DB->get_record('feedback_sitecourse_map', $params)) {
                return false;
            }
        }
        // No mapping means any course is mapped.
        return true;
    }

    /**
     * If there are any new responses to the anonymous feedback, re-shuffle all
     * responses and assign response number to each of them.
     */
    public function shuffle_anonym_responses() {
        global $DB;
        $params = array('feedback' => $this->feedback->id,
            'random_response' => 0,
            'anonymous_response' => FEEDBACK_ANONYMOUS_YES);

        if ($DB->count_records('feedback_completed', $params, 'random_response')) {
            // Get all of the anonymous records, go through them and assign a response id.
            unset($params['random_response']);
            $feedbackcompleteds = $DB->get_records('feedback_completed', $params, 'id');
            shuffle($feedbackcompleteds);
            $num = 1;
            foreach ($feedbackcompleteds as $compl) {
                $compl->random_response = $num++;
                $DB->update_record('feedback_completed', $compl);
            }
        }
    }

    /**
     * Counts records from {feedback_completed} table for a given feedback
     *
     * If $groupid or $this->courseid is set, the records are filtered by the group/course
     *
     * @param int $groupid
     * @return mixed array of found completeds otherwise false
     */
    public function count_completed_responses($groupid = 0) {
        global $DB;
        if (intval($groupid) > 0) {
            $query = "SELECT COUNT(DISTINCT fbc.id)
                        FROM {feedback_completed} fbc, {groups_members} gm
                        WHERE fbc.feedback = :feedback
                            AND gm.groupid = :groupid
                            AND fbc.userid = gm.userid";
        } else if ($this->courseid) {
            $query = "SELECT COUNT(fbc.id)
                        FROM {feedback_completed} fbc
                        WHERE fbc.feedback = :feedback
                            AND fbc.courseid = :courseid";
        } else {
            $query = "SELECT COUNT(fbc.id) FROM {feedback_completed} fbc WHERE fbc.feedback = :feedback";
        }
        $params = ['feedback' => $this->feedback->id, 'groupid' => $groupid, 'courseid' => $this->courseid];
        return $DB->get_field_sql($query, $params);
    }

    /**
     * For the frontpage feedback returns the list of courses with at least one completed feedback
     *
     * @return array id=>name pairs of courses
     */
    public function get_completed_courses() {
        global $DB;

        if ($this->get_feedback()->course != SITEID) {
            return [];
        }

        if ($this->allcourses !== null) {
            return $this->allcourses;
        }

        $courseselect = "SELECT fbc.courseid
            FROM {feedback_completed} fbc
            WHERE fbc.feedback = :feedbackid";

        $ctxselect = context_helper::get_preload_record_columns_sql('ctx');

        $sql = 'SELECT c.id, c.shortname, c.fullname, c.idnumber, c.visible, '. $ctxselect. '
                FROM {course} c
                JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextcourse
                WHERE c.id IN ('. $courseselect.') ORDER BY c.sortorder';
        $list = $DB->get_records_sql($sql, ['contextcourse' => CONTEXT_COURSE, 'feedbackid' => $this->get_feedback()->id]);

        $this->allcourses = array();
        foreach ($list as $course) {
            context_helper::preload_from_record($course);
            if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
                // Do not return courses that current user can not see.
                continue;
            }
            $label = get_course_display_name_for_list($course);
            $this->allcourses[$course->id] = $label;
        }
        return $this->allcourses;
    }

    /**
     * Import feedback structure from XML file
     *
     * @param string $xmlcontent
     * @param bool $deleteolditems
     * @return bool
     */
    public function import($xmlcontent, $deleteolditems) {
        global $CFG;
        require_once $CFG->libdir."/xmlize.php";

        $data = xmlize($xmlcontent, 0);
        $version = intval($data['FEEDBACK']['@']['VERSION']);
        if ($version == 200701) {
            $import = new mod_feedback_import_legacy($this);
            return $import->import($xmlcontent, $deleteolditems);
        }

        if ($version < 201706) {
            return false;
        }

        if (!isset($data['FEEDBACK']['#']['ITEMS'][0]['#']['ITEM'])) {
            // Potentially broken structure of XML file.
            return false;
        }

        // Perform import.
        if ($deleteolditems) {
            feedback_delete_all_items($this->get_feedback()->id);
        }

        $importeditems = $data['FEEDBACK']['#']['ITEMS'][0]['#']['ITEM'];
        $idsmap = [];
        $context = context_module::instance($this->get_cm()->id);
        foreach ($importeditems as $importeditem) {
            $typ = $importeditem['@']['TYPE'];
            if (!$itemobj = feedback_get_item_class($typ, IGNORE_MISSING)) {
                \core\notification::add(get_string('typenotfound', 'feedback', $typ), \core\output\notification::NOTIFY_ERROR);
                continue;
            }
            if ($itemid = $itemobj->import($importeditem, $this->get_feedback(), $version, $idsmap)) {
                // Save the map of ids in the export file and in the actual feedback (we need it to remap dependencies).
                $idsmap[$importeditem['@']['ID']] = $itemid;
                // Import files.
                $this->import_files($importeditem, 'ITEMFILES', $context->id, 'item', $itemid);
            }
        }

        // Reset items cache.
        $this->allitems = null;

        return true;
    }

    /**
     * Returns string for an XML tag
     *
     * @param string $tagname
     * @param string $contents
     * @return string
     */
    protected function export_xml_tag($tagname, $contents) {
        return "<$tagname>".preg_replace("/\r\n|\r/", "\n", s($contents))."</$tagname>\n";
    }

    /**
     * Exports feedback to XML file
     *
     * @return string contents of the XML file
     */
    public function export() {
        if ($this->is_empty()) {
            return false;
        }

        $spacer = '  ';
        $data = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
        $data .= '<FEEDBACK VERSION="201706" COMMENT="XML-Importfile for mod/feedback">' . "\n";
        $data .= $spacer . '<ITEMS>'."\n";
        $items = $this->get_items();
        $context = context_module::instance($this->get_cm()->id);
        foreach ($items as $item) {
            $data .= $spacer.$spacer.'<ITEM ID="'.$item->id.'" TYPE="'.$item->typ.'">'."\n";
            if (!$itemobj = feedback_get_item_class($item->typ, IGNORE_MISSING)) {
                continue;
            }
            $exportvalues = $itemobj->prepare_for_export($item);
            foreach ($exportvalues as $key => $value) {
                $tag = core_text::strtoupper($key);
                $data .= $spacer . $spacer . $spacer . $this->export_xml_tag($tag, $value);
            }
            $data .= $this->export_files($spacer . $spacer . $spacer, 'ITEMFILES', $context->id, 'item', $item->id);
            $data .= $spacer . $spacer . '</ITEM>'."\n";
        }
        $data .= $spacer . '</ITEMS>'."\n";
        $data .= '</FEEDBACK>' . "\n";

        return $data;
    }

    /**
     * Exports all files in the specified file area as an XML
     *
     * @param string $prefix prefix for each line of the XML file (spaces will be added for sub nodes)
     * @param string $tag
     * @param int $contextid
     * @param string $filearea
     * @param int $itemid
     * @return string
     */
    protected function export_files($prefix, $tag, $contextid, $filearea, $itemid) {
        $co = '';
        $spacer = '  ';
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($contextid, 'mod_feedback', $filearea, $itemid, 'itemid,filepath,filename', false)) {
            $co .= $prefix."<$tag>\n";
            foreach ($files as $file) {
                $co .= $prefix.$spacer."<FILE>\n";
                $co .= $prefix.$spacer.$spacer.$this->export_xml_tag('FILENAME', $file->get_filename());
                $co .= $prefix.$spacer.$spacer.$this->export_xml_tag('FILEPATH', $file->get_filepath());
                $co .= $prefix.$spacer.$spacer.$this->export_xml_tag('CONTENTS', base64_encode($file->get_content()));
                $co .= $prefix.$spacer.$spacer.$this->export_xml_tag('FILEAUTHOR', $file->get_author());
                $co .= $prefix.$spacer.$spacer.$this->export_xml_tag('FILELICENSE', $file->get_license());
                $co .= $prefix.$spacer."</FILE>\n";
            }
            $co .= $prefix."</$tag>\n";
        }
        return $co;
    }

    /**
     * Parses files from XML import and inserts them into file system
     *
     * @param array $xmlparent parent element in parsed XML tree
     * @param string $tag
     * @param int $contextid
     * @param string $filearea
     * @param int $itemid
     * @return int number of files imported
     */
    protected function import_files($xmlparent, $tag, $contextid, $filearea, $itemid) {
        global $USER, $CFG;
        $count = 0;
        if (empty($xmlparent['#'][$tag][0]['#']['FILE'])) {
            return $count;
        }

        $fs = get_file_storage();
        $files = $xmlparent['#'][$tag][0]['#']['FILE'];
        foreach ($files as $file) {
            $filerecord = array(
                'contextid' => $contextid,
                'component' => 'mod_feedback',
                'filearea'  => $filearea,
                'itemid'    => $itemid,
                'filepath'  => $file['#']['FILEPATH'][0]['#'],
                'filename'  => $file['#']['FILENAME'][0]['#'],
                'userid'    => $USER->id
            );
            if (array_key_exists('FILEAUTHOR', $file['#'])) {
                $filerecord['author'] = $file['#']['FILEAUTHOR'][0]['#'];
            }
            if (array_key_exists('FILELICENSE', $file['#'])) {
                $license = $file['#']['FILELICENSE'][0]['#'];
                require_once($CFG->libdir . "/licenselib.php");
                if (license_manager::get_license_by_shortname($license)) {
                    $filerecord['license'] = $license;
                }
            }
            $content =  $file['#']['CONTENTS'][0]['#'];
            $fs->create_file_from_string($filerecord, base64_decode($content));
            $count++;
        }
        return $count;
    }

    /**
     * Checks if analysis page can be shown for a group
     *
     * Returns false if feedback is anonymous, group is specified and there are less than
     * FEEDBACK_MIN_ANONYMOUS_COUNT_IN_GROUP responses
     *
     * @param int $groupid
     * @return bool
     */
    public function has_sufficient_responses_for_group($groupid = 0) {
        // Check if we have enough results to print analysis.
        if ($groupid > 0 AND $this->feedback->anonymous == FEEDBACK_ANONYMOUS_YES) {
            $completedcount = $this->count_completed_responses($groupid);
            if ($completedcount < FEEDBACK_MIN_ANONYMOUS_COUNT_IN_GROUP) {
                return false;
            }
        }
        return true;
    }
}