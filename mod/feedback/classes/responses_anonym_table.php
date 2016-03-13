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
 * Contains class mod_feedback_responses_anonym_table
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Class mod_feedback_responses_anonym_table
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_responses_anonym_table extends flexible_table {

    /** @var cm_info */
    protected $cm;

    /** @var array */
    protected $responses = array();

    /**
     * Constructor
     *
     * @param string $uniqueid
     * @param cm_info $cm
     * @param bool $showall
     * @param int $perpage
     */
    public function __construct($uniqueid, cm_info $cm, $showall, $perpage) {
        global $DB;

        $this->cm = $cm;
        $context = context_module::instance($cm->id);

        parent::__construct($uniqueid);

        $params = array('feedback' => $cm->instance, 'anonymous_response' => FEEDBACK_ANONYMOUS_YES);
        $feedbackcompletedscount = $DB->count_records('feedback_completed', $params);

        // Preparing the table for output.
        $this->define_baseurl(new moodle_url('/mod/feedback/show_entries.php',
                array('id' => $cm->id, 'showall' => $showall)));

        $tablecolumns = array('response', 'showresponse');
        $tableheaders = array('', '');

        if (has_capability('mod/feedback:deletesubmissions', $context)) {
            $tablecolumns[] = 'deleteentry';
            $tableheaders[] = '';
        }

        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);

        $this->sortable(false);
        $this->set_attribute('id', 'showentryanonymtable');
        $this->setup();

        $matchcount = $feedbackcompletedscount;
        $this->initialbars(true);

        if ($showall) {
            $startpage = 0;
            $pagecount = 0;
            $this->totalrows = $matchcount;
        } else {
            $this->pagesize($perpage, $matchcount);
            $startpage = $this->get_page_start();
            $pagecount = $this->get_page_size();
        }

        $this->responses = $DB->get_records('feedback_completed',
            $params, 'random_response', 'id, random_response', $startpage, $pagecount);
    }

    /**
     * Prints the table.
     */
    public function print_html() {
        global $OUTPUT;
        $context = context_module::instance($this->cm->id);

        if ($this->totalrows) {
            $this->start_output();
        }

        foreach ($this->responses as $compl) {
            $data = array();

            $data[] = get_string('response_nr', 'feedback').': '. $compl->random_response;

            // Link to the entry.
            $showentryurl = new moodle_url($this->baseurl, array('showcompleted' => $compl->id));
            $data[] = html_writer::link($showentryurl, get_string('show_entry', 'feedback'));

            // Link to delete the entry.
            if (has_capability('mod/feedback:deletesubmissions', $context)) {
                $deleteentryurl = new moodle_url($this->baseurl, array('delete' => $compl->id));
                $data[] = html_writer::link($deleteentryurl, get_string('delete_entry', 'feedback'));
            }
            $this->add_data($data);
        }

        parent::print_html();

        // Toggle 'Show all' link.
        if ($this->totalrows) {
            if (!$this->use_pages) {
                echo $OUTPUT->container(html_writer::link(new moodle_url($this->baseurl, array('showall' => 0)),
                        get_string('showperpage', '', FEEDBACK_DEFAULT_PAGE_COUNT)), array(), 'showall');
            } else if ($this->totalrows > 0 && $this->pagesize < $this->totalrows) {
                echo $OUTPUT->container(html_writer::link(new moodle_url($this->baseurl, array('showall' => 1)),
                        get_string('showall', '', $this->totalrows)), array(), 'showall');
            }
        }
    }

    /**
     * Print message if there are no results.
     */
    public function print_nothing_to_display() {
        global $OUTPUT;
        echo $OUTPUT->box(get_string('nothingtodisplay'), 'generalbox nothingtodisplay');
    }

    /**
     * Generate the HTML for the table preferences reset button.
     */
    protected function render_reset_button() {
        return '';
    }
}
