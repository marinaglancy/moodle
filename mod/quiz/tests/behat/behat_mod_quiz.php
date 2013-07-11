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
 * Steps definitions related with the forum activity.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode;
/**
 * Forum-related steps definitions.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_quiz extends behat_base {

    /**
     * Adds random answers to the quiz. The step begins from the home page with no currently logged in user.
     *
     * @Given /^I give random answers to the quiz "(?P<quiz_name>(?:[^"]|\\")*)" in course "(?P<course_name>(?:[^"]|\\")*)" as user "(?P<user_name>(?:[^"]|\\")*)"$/
     * @param string $quizname
     * @param string $username
     * @param string $coursename
     */
    public function i_give_random_answers_to_the_quiz_in_course_as_user($quizname, $coursename, $username) {

        // Escaping arguments as it has been stripped automatically by the transformer.
        return array(
            new Given('I log in as "' . $this->escape($username) . '"'),
            new Given('I follow "' . $this->escape($coursename) . '"'),
            new Given('I follow "' . $this->escape($quizname) . '"'),
            new Given('I press "Attempt quiz now"'),
            new Given('I select "'.((rand(1,100)%2) ? 'True' : 'False').'" radio button'),
            new Given('I press "Next"'),
            new Given('I press "Submit all and finish"'),
            new Given('I press "Submit all and finish"'),
            new Given('I log out'),
        );
    }
}