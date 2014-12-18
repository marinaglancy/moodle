@core @core_course @javascript
Feature: Edit completion settings of an activity
  In order to edit completion settings without accidentally breaking user data
  As a teacher
  I need to edit the activity and use the unlock button if required

  Background:
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
    And I log out
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "activities" exist:
      | activity | course | idnumber | name     | intro | content | completion | completionview |
      | page     | C1     | x        | TestPage | x     | x       | 2          | 1              |
    And I log in as "admin"
    And I follow "Course 1"

  Scenario: Completion is not locked when the activity has not yet been viewed
    Given I turn editing mode on
    And I open "TestPage" actions menu
    And I click on "Edit settings" "link" in the "TestPage" activity
    When I expand all fieldsets
    Then I should see "Completion tracking"
    And I should not see "Completion options locked"

  Scenario: Completion is locked after the activity has been viewed
    Given I follow "TestPage"
    When I follow "Edit settings"
    And I expand all fieldsets
    Then I should see "Completion options locked"

  Scenario: Pressing the unlock button allows the user to edit completion settings
    Given I follow "TestPage"
    When I follow "Edit settings"
    And I expand all fieldsets
    And I press "Unlock completion options"
    Then I should see "Completion options unlocked"
    And I set the field "Completion tracking" to "Students can manually mark the activity as completed"
    And I press "Save and display"
    And I follow "Edit settings"
    And I expand all fieldsets
    Then the field "Completion tracking" matches value "Students can manually mark the activity as completed"

  Scenario: Even when completion is locked, the user can still set the date
    Given I follow "TestPage"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I click on "id_completionexpected_enabled" "checkbox"
    And I set the field "id_completionexpected_year" to "2013"
    And I press "Save and display"
    And I follow "Edit settings"
    And I expand all fieldsets
    Then the field "id_completionexpected_year" matches value "2013"
