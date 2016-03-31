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
 * Contains class mod_feedback_responses_anon_table
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Class mod_feedback_responses_anon_table
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_responses_anon_table extends table_sql {

    /** @var cm_info */
    protected $cm;

    /** @var int */
    protected $grandtotal = null;

    /**
     * Constructor
     *
     * @param cm_info $cm
     */
    public function __construct(cm_info $cm) {

        $this->cm = $cm;
        $context = context_module::instance($cm->id);

        parent::__construct('feedback-showentryanonym-list-' . $cm->course);
        $this->request[TABLE_VAR_PAGE] = 'apage';

        $tablecolumns = array('response', 'showresponse');
        $tableheaders = array('', '');

        if (has_capability('mod/feedback:deletesubmissions', $context)) {
            $tablecolumns[] = 'deleteentry';
            $tableheaders[] = '';
        }

        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);

        $this->sortable(false, 'response');
        $this->collapsible(false);
        $this->set_attribute('id', 'showentryanonymtable');

        $params = array('instance' => $this->cm->instance, 'anon' => FEEDBACK_ANONYMOUS_YES);

        $fields = 'id, random_response AS response';
        $from = '{feedback_completed}';
        $where = 'anonymous_response = :anon AND feedback = :instance';

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(id) FROM $from WHERE $where", $params);
    }

    /**
     * Prepares column reponse for display
     * @param stdClass $row
     * @return string
     */
    public function col_response($row) {
        return get_string('response_nr', 'feedback').': '. $row->response;
    }

    /**
     * Prepares column showresponse for display
     * @param stdClass $row
     * @return string
     */
    public function col_showresponse($row) {
        $showentryurl = new moodle_url($this->baseurl, array('showcompleted' => $row->id));
        return html_writer::link($showentryurl, get_string('show_entry', 'feedback'));
    }

    /**
     * Prepares column deleteentry for display
     * @param stdClass $row
     * @return string
     */
    public function col_deleteentry($row) {
        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/feedback:deletesubmissions', $context)) {
            $deleteentryurl = new moodle_url('/mod/feedback/show_entries.php',
                array('id' => $this->cm->id, 'delete' => $row->id));
            return html_writer::link($deleteentryurl, get_string('delete_entry', 'feedback'));
        }
    }

    /**
     * Generate the HTML for the table preferences reset button.
     */
    protected function render_reset_button() {
        return '';
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
     */
    public function query_db($pagesize, $useinitialsbar=true) {
        global $DB;
        $this->totalrows = $grandtotal = $this->get_total_responses_count();
        $this->initialbars($useinitialsbar);

        if ($this->totalrows > $pagesize) {
            $this->pagesize($pagesize, $this->totalrows);
        }

        $sql = "SELECT
                {$this->sql->fields}
                FROM {$this->sql->from}
                WHERE {$this->sql->where}
                ORDER BY " . $this->get_sql_sort();

        $this->rawdata = $DB->get_records_sql($sql, $this->sql->params, $this->get_page_start(), $this->get_page_size());
    }

    /**
     * Returns total number of reponses (without any filters applied)
     * @return int
     */
    public function get_total_responses_count() {
        global $DB;
        if ($this->grandtotal === null) {
            $this->grandtotal = $DB->count_records_sql($this->countsql, $this->countparams);
        }
        return $this->grandtotal;
    }

    /**
     * Displays the table
     */
    public function display() {
        global $OUTPUT;
        $grandtotal = $this->get_total_responses_count();
        if (!$grandtotal) {
            echo $OUTPUT->box(get_string('nothingtodisplay'), 'generalbox nothingtodisplay');
            return;
        }
        $showall = optional_param('ashowall', 0, PARAM_BOOL);
        $this->define_baseurl(new moodle_url('/mod/feedback/show_entries.php',
            array('id' => $this->cm->id, 'showall' => $showall)));
        $this->out($showall ? $grandtotal : FEEDBACK_DEFAULT_PAGE_COUNT, false);

        // Toggle 'Show all' link.
        if ($this->totalrows > FEEDBACK_DEFAULT_PAGE_COUNT) {
            if (!$this->use_pages) {
                echo html_writer::div(html_writer::link(new moodle_url($this->baseurl, array('ashowall' => 0)),
                    get_string('showperpage', '', FEEDBACK_DEFAULT_PAGE_COUNT)), 'showall');
            } else {
                echo html_writer::div(html_writer::link(new moodle_url($this->baseurl, array('ashowall' => 1)),
                    get_string('showall', '', $this->totalrows)), 'showall');
            }
        }
    }
}
