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
 * Unit tests for the filetypes helper.
 *
 * @package core_form
 * @category test
 * @copyright 2016 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/filetypes.php');
require_once($CFG->libdir.'/formslib.php');

/**
 * Unit tests for the filetypes helper.
 *
 * @package core_form
 * @copyright 2016 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_form_filetypes_testcase extends basic_testcase {
    /**
     * Test fetching all system-level file types and groups.
     */
    public function test_all_types() {
        $types = new core_form\filetypes();
        $this->assertNotEmpty($types->get_typegroups());
        $this->assertNotEmpty($types->get_alltypes());
    }

    /**
     * Test fetching a single file type.
     */
    public function test_specific_type() {
        $types = new core_form\filetypes(array('.pdf'));
        $this->assertEquals(
            array(
                'document' => (object)array(
                    'name' => 'Document files',
                    'types' => array('.pdf'),
                    'isoption' => false,
                    // Note: extlist is not returned in this situation.
                )
            ),
            $types->get_typegroups()
        );
        $this->assertEquals(
            array(
                '.pdf' => (object)array(
                    'name' => 'PDF document',
                    'extlist' => '.pdf',
                )
            ),
            $types->get_alltypes()
        );
    }

    /**
     * Test fetching a single type group.
     */
    public function test_typegroup() {
        $types = new core_form\filetypes(array('document'));
        $typegroups = $types->get_typegroups();
        $alltypes = $types->get_alltypes();

        $this->assertNotEmpty($typegroups);
        $this->assertNotEmpty($alltypes);

        $this->assertArrayHasKey('document', $typegroups);
        $this->assertArrayHasKey('.pdf', $alltypes);

        // When fetching a full type group, we expect to see the extensions list,
        // and that it be a choosable option.
        $this->assertObjectHasAttribute('extlist', $typegroups['document']);
        $this->assertTrue($typegroups['document']->isoption);
    }

    /**
     * Test the form element without any limit to choices.
     */
    public function test_formelem_nolimit() {
        $form = new temp_form_filetypes();
        $mform = $form->getform();
        $el = $mform->addElement('filetypes', 'testel', null, null);

        $values = array('testel' => array('value' => 'document,.pdf,.nonsense'));
        $this->assertEquals('document,.pdf', $el->exportValue($values));
    }

    /**
     * Test the form element with limited choices.
     */
    public function test_formelem_limited() {
        $form = new temp_form_filetypes();
        $mform = $form->getform();
        $el = $mform->addElement('filetypes', 'testel', null, array('.pdf'));

        $values = array('testel' => array('value' => 'document,.pdf,.nonsense'));
        $this->assertEquals('.pdf', $el->exportValue($values));
    }

}

/**
 * Form object to be used in test case.
 * @copyright 2016 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class temp_form_filetypes extends moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        // No definition required.
    }

    /**
     * Returns form reference
     * @return MoodleQuickForm
     */
    public function getform() {
        $mform = $this->_form;
        // Set submitted flag, to simulate submission.
        $mform->_flagSubmitted = true;
        return $mform;
    }
}
