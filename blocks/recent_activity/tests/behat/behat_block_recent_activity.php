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
 * Recent activity block steps definitions.
 *
 * @package    block_recent_activity
 * @category   test
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Recent activity block steps definitions.
 *
 * @package    block_recent_activity
 * @category   test
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_recent_activity extends behat_base {

    /**
     * Ensures that recent activity block looks exactly as we expect.
     *
     * This step can be used for the most activities types (except forum) that
     * don't enclose the list in <ul> tag.
     *
     * @Then /^I should see in recent activity block:$/
     * @param TableNode $table each row represents a row in a block, first column contains
     *     css descriptor and second contains the text that has to be present.
     * @throws ElementNotFoundException
     */
    public function i_should_see_in_recent_activity_block(TableNode $table) {
        // XPath for the recent activity block contents.
        $blockcontentpath = "//*[contains(concat(' ', normalize-space(@class), ' '), ' block_recent_activity ')]".
                "/*[contains(concat(' ', normalize-space(@class), ' '), ' content ')]";
        // XPath for all children except those with class .activityhead.
        $childrenselector = "*[not(contains(concat(' ',normalize-space(@class),' '),' activityhead '))]";
        $childrenpath = $blockcontentpath;//."/".$childrenselector;
        $rows = $table->getRows();
        // XPath that ensures that number of children matches number of rows in table.
        $xpathcount = $blockcontentpath.'/.[count('.$childrenselector.')='.count($rows).']';

        $subpaths = array();
        for ($i=0; $i<count($rows); $i++) {
            if (substr($rows[$i][0], 0, 1) === '.') {
                $classname = $this->getSession()->getSelectorsHandler()->xpathLiteral(' '.substr($rows[$i][0], 1).' ');
                $locator = "contains(concat(' ',normalize-space(@class),' '),$classname)";
            } else {
                $elementname = $this->getSession()->getSelectorsHandler()->xpathLiteral(strtoupper($rows[$i][0]));
                $locator = "name()=$elementname";
            }
            $text = $this->getSession()->getSelectorsHandler()->xpathLiteral($rows[$i][1]);
            // XPath portion that ensures that ($i+1)th child has required tag/class name and contains required text.
            $subpaths[] = "{$childrenselector}[".($i+1)."][$locator and contains(.,$text)]";
        }
        // XPath that merges XPath portions for all children.
        $xpath = $childrenpath.'/.['.join(' and ', $subpaths).']';

        return array(
            new Given('"'.$this->escape($xpathcount).'" "xpath_element" should exist'),
            new Given('"'.$this->escape($xpath).'" "xpath_element" should exist'),
        );
    }

    /**
     * Ensures that recent activity block contains the expected list under the specified heading.
     *
     * This step can be used for modules such as forum that enclose the list in <ul> tag.
     *
     * @Then /^I should see in recent activity block under "(?P<heading_string>(?:[^"]|\\")*)":$/
     * @param TableNode $table each row represents a row in a block, first column contains
     *     css descriptor and second contains the text that has to be present.
     * @throws ElementNotFoundException
     */
    public function i_should_see_in_recent_activity_block_under($heading, TableNode $table) {
        $rows = $table->getRows();
        // This is almost a complete copy of block_recent_activity::i_should_see_in_recent_activity_block()
        // except that the selector is different because forum wraps entires in <ul>

        // XPath for the recent activity block contents.
        $headingliteral = $this->getSession()->getSelectorsHandler()->xpathLiteral($heading);
        $blockcontentpath = "//*[contains(concat(' ', normalize-space(@class), ' '), ' block_recent_activity ')]".
                "/*[contains(concat(' ', normalize-space(@class), ' '), ' content ')]".
                "/h3[contains(.,$headingliteral)]/following-sibling::ul[1]";
        $childrenselector = '(li/div)';

        // XPath that ensures that number of children matches number of rows in table.
        $xpathcount = $blockcontentpath.'/.[count('.$childrenselector.')='.count($rows).']';

        $subpaths = array();
        for ($i=0; $i<count($rows); $i++) {
            if (substr($rows[$i][0], 0, 1) === '.') {
                $classname = $this->getSession()->getSelectorsHandler()->xpathLiteral(' '.substr($rows[$i][0], 1).' ');
                $locator = "contains(concat(' ',normalize-space(@class),' '),$classname)";
            } else {
                $elementname = $this->getSession()->getSelectorsHandler()->xpathLiteral(strtoupper($rows[$i][0]));
                $locator = "name()=$elementname";
            }
            $text = $this->getSession()->getSelectorsHandler()->xpathLiteral($rows[$i][1]);
            // XPath portion that ensures that ($i+1)th child has required tag/class name and contains required text.
            $subpaths[] = "{$childrenselector}[".($i+1)."][$locator and contains(.,$text)]";
        }
        // XPath that merges XPath portions for all children.
        $xpath = $blockcontentpath.'/.['.join(' and ', $subpaths).']';

        return array(
            new Given('"'.$this->escape($xpathcount).'" "xpath_element" should exist'),
            new Given('"'.$this->escape($xpath).'" "xpath_element" should exist'),
        );
    }

    /**
     * Ensures that recent activity block dos not contain any records.
     *
     * @Then /^I should see nothing in recent activity block$/
     * @throws ElementNotFoundException
     */
    public function i_should_see_nothing_in_recent_activity_block() {
        $str1 = $this->escape(get_string('nothingnew'));
        $str2 = $this->escape(get_string('pluginname', 'block_recent_activity'));
        return new Given("I should see \"$str1\" in the \"$str2\" \"block\"");
    }
}
