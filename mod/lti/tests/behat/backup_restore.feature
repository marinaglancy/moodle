@mod @mod_lti @core_backup @javascript
Feature: Restoring Moodle 2 backup restores LTI configuration

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | idnumber |
      | Course 1 | C1 | 0 | C1 |

  Scenario: Backup and restore course with site LTI tool on the same site
    When I log in as "admin"
    And I navigate to "Manage tools" node in "Site administration > Plugins > Activity modules > LTI"
    And I follow "Manage preconfigured tools"
    And I follow "Add preconfigured tool"
    And I set the following fields to these values:
      | Tool name | My site tool |
      | Tool base URL | https://www.moodle.org |
      | lti_coursevisible | 1 |
    And I press "Save changes"
    And "This tool has not yet been used" "text" should exist in "//div[contains(@id,'tool-card-container') and contains(., 'My site tool')]" "xpath_element"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "External tool" to section "1" and I fill the form with:
        | Activity name | My LTI module |
        | Preconfigured tool | My site tool |
        | Launch container | Embed |
    And I follow "Course 1"
    And I should see "My LTI module"
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
    And I am on site homepage
    And I follow "Course 1 copy 1"
    And I open "My LTI module" actions menu
    And I click on "Edit settings" "link" in the "My LTI module" activity
    Then the field "Preconfigured tool" matches value "My site tool"
    And I navigate to "Manage tools" node in "Site administration > Plugins > Activity modules > LTI"
    And "This tool is being used 2 times" "text" should exist in "//div[contains(@id,'tool-card-container') and contains(., 'My site tool')]" "xpath_element"

