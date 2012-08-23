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
 * Provides code used during file browsing
 *
 * @category  files
 * @package   mod_workshop
 * @copyright 2009 David Mudrak <david.mudrak@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Represents virtual root node for all submissions
 *
 * Workshop submission uses two fileareas: submission_content for editor's embeded media
 * and submission_attachment for attachments. In both, the itemid represents the submission id.
 * This container is used to display the list of all submissions in these areas (ie when
 * these areas are browsed with itemid == null).
 */
class workshop_file_info_submissions_container extends file_info {
    protected $course;
    protected $cm;
    protected $areas;
    protected $filearea;

    public function __construct($browser, $course, $cm, $context, $areas, $filearea) {
        parent::__construct($browser, $context);
        $this->course   = $course;
        $this->cm       = $cm;
        $this->areas    = $areas;
        $this->filearea = $filearea;
    }

    /**
     * Returns list of standard virtual file/directory identification.
     * The difference from stored_file parameters is that null values
     * are allowed in all fields
     * @return array with keys contextid, filearea, itemid, filepath and filename
     */
    public function get_params() {
        return array('contextid'=>$this->context->id,
                     'component'=>'mod_workshop',
                     'filearea' =>$this->filearea,
                     'itemid'   =>null,
                     'filepath' =>null,
                     'filename' =>null);
    }

    /**
     * Returns localised visible name.
     * @return string
     */
    public function get_visible_name() {
        return $this->areas[$this->filearea];
    }

    /**
     * Can I add new files or directories?
     * @return bool
     */
    public function is_writable() {
        return false;
    }

    /**
     * Is directory?
     * @return bool
     */
    public function is_directory() {
        return true;
    }

    /**
     * Returns list of children.
     * @return array of file_info instances
     */
    public function get_children() {
        global $DB;

        $children = array();
        $itemids = $DB->get_records('files', array('contextid' => $this->context->id, 'component' => 'mod_workshop', 'filearea' => $this->filearea),
            'itemid', "DISTINCT itemid");
        foreach ($itemids as $itemid => $unused) {
            if ($child = $this->browser->get_file_info($this->context, 'mod_workshop', $this->filearea, $itemid)) {
                $children[] = $child;
            }
        }
        return $children;
    }

    /**
     * Returns list of children which are either files matching the specified extensions
     * or folders that contain at least one such file.
     *
     * @param string|array $extensions, either '*' or array of lowercase extensions, i.e. array('.gif','.jpg')
     * @return array of file_info instances
     */
    public function get_non_empty_children($extensions = '*') {
        global $DB;
        $children = array();
        $params1 = array('contextid' => $this->context->id, 'component' => 'mod_workshop',
            'filearea' => $this->filearea, 'emptyfilename' => '.');
        list($sql2, $params2) = $this->build_search_files_sql($extensions);
        $itemids = $DB->get_records_sql(
                'SELECT DISTINCT itemid FROM {files}
                    WHERE contextid = :contextid
                    AND component = :component
                    AND filearea = :filearea
                    AND filename <> :emptyfilename '.$sql2.
                'ORDER BY itemid',
                array_merge($params1, $params2));
        foreach ($itemids as $itemid => $unused) {
            if ($child = $this->browser->get_file_info($this->context, 'mod_workshop', $this->filearea, $itemid)) {
                if ($child->count_non_empty_children($extensions)) {
                    $children[] = $child;
                }
            }
        }
        return $children;
    }

    /**
     * Returns the number of children which are either files matching the specified extensions
     * or folders containing at least one such file.
     *
     * NOTE: We don't need the exact number of non empty children if it is >=2
     * In this function 1 is never returned to avoid skipping the single subfolder
     *
     * @param string|array $extensions, for example '*' or array('.gif','.jpg')
     * @return int
     */
    public function count_non_empty_children($extensions = '*') {
        global $DB;
        $cnt = 0;
        $params1 = array('contextid' => $this->context->id, 'component' => 'mod_workshop',
            'filearea' => $this->filearea, 'emptyfilename' => '.');
        list($sql2, $params2) = $this->build_search_files_sql($extensions);
        $itemids = $DB->get_recordset_sql(
                'SELECT DISTINCT itemid FROM {files}
                    WHERE contextid = :contextid
                    AND component = :component
                    AND filearea = :filearea
                    AND filename <> :emptyfilename '.$sql2,
                array_merge($params1, $params2));
        foreach ($itemids as $record) {
            if ($cnt > 1) {
                break;
            }
            if ($child = $this->browser->get_file_info($this->context, 'mod_workshop', $this->filearea, $record->itemid)) {
                if ($child->count_non_empty_children($extensions)) {
                    $cnt++;
                }
            }
        }
        return $cnt;
    }

    /**
     * Returns parent file_info instance
     * @return file_info or null for root
     */
    public function get_parent() {
        return $this->browser->get_file_info($this->context);
    }
}
