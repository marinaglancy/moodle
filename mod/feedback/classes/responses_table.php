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
 * Contains class mod_feedback_responses_table
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Class mod_feedback_responses_table
 *
 * @package   mod_feedback
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_responses_table extends flexible_table {

    /** @var cm_info */
    protected $cm;

    /** @var array */
    protected $students = array();

    /**
     * Constructor
     *
     * @param string $uniqueid
     * @param cm_info $cm
     * @param bool $showall
     * @param int $perpage
     */
    public function __construct($uniqueid, cm_info $cm, $showall, $perpage) {
        $this->cm = $cm;
        $context = context_module::instance($cm->id);

        parent::__construct($uniqueid);

        // Prepare the table for output.
        $this->define_baseurl(new moodle_url('/mod/feedback/show_entries.php',
                array('id' => $cm->id, 'showall' => $showall)));

        $tablecolumns = array('userpic', 'fullname', 'completed_timemodified');
        $tableheaders = array(get_string('userpic'), get_string('fullnameuser'), get_string('date'));

        if (has_capability('mod/feedback:deletesubmissions', $context)) {
            $tablecolumns[] = 'deleteentry';
            $tableheaders[] = '';
        }

        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);

        $this->sortable(true, 'lastname', SORT_ASC);
        $this->set_attribute('id', 'showentrytable');
        $this->setup();

        if ($this->get_sql_sort()) {
            $sort = $this->get_sql_sort();
        } else {
            $sort = '';
        }

        list($where, $params) = $this->get_sql_where();
        if ($where) {
            $where .= ' AND';
        }

        // Get the list of responses for the selected group (if applicable).
        $usedgroupid = groups_get_activity_group($cm);
        $matchcount = feedback_count_complete_users($cm, $usedgroupid);
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

        $this->students = feedback_get_complete_users($cm, $usedgroupid, $where, $params, $sort, $startpage, $pagecount);
    }

    /**
     * Prints the table.
     */
    public function print_html() {
        global $OUTPUT;

        $cm = $this->cm;
        $context = context_module::instance($cm->id);
        $canviewfullname = has_capability('moodle/site:viewfullnames', $context);

        if ($this->totalrows) {
            $this->start_output();
        }

        foreach ($this->students as $student) {
            // Userpicture and link to the profilepage.
            $nameurl = new moodle_url('/user/view.php', array('id' => $student->id, 'course' => $cm->course));
            $fullname = fullname($student, $canviewfullname);
            $data = array ($OUTPUT->user_picture($student, array('courseid' => $cm->course)),
                    html_writer::link($nameurl, $fullname));

            // Link to the entry of the user.
            $showentryurl = new moodle_url($this->baseurl, array('userid' => $student->id,
                'showcompleted' => $student->completed_id));
            $data[] = html_writer::link($showentryurl, userdate($student->completed_timemodified));

            // Link to delete the entry.
            if (has_capability('mod/feedback:deletesubmissions', $context)) {
                $deleteentryurl = new moodle_url('/mod/feedback/show_entries.php',
                        array('id' => $cm->id, 'delete' => $student->completed_id));
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
}
