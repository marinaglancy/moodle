@core @core_group
Feature: Add/Remove Users
  In order to organize course activities in groups
  As a teacher
  I need to be able to add and remove students from a group

  Background:
    Given the following config values are set as admin:
      | maxusersperpage | 100 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student3@example.com |
      | student4 | Student | 4 | student4@example.com |
    And the following "course enrolments" exist:
      | user     | course | role | status |
      | teacher1 | C1 | editingteacher | 0 |
      | student1 | C1 | student | 0 |
      | student2 | C1 | student | 0 |
      | student3 | C1 | student | 0 |
      | student4 | C1 | student | 0 |
    And the following "groups" exist:
      | name    | description | course  | idnumber |
      | Group 1 | Anything    | C1 | GROUP1   |
      | Group 2 | Anything    | C1 | GROUP2   |
      | Group 3 | Anything    | C1 | GROUP3   |
    Given I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I expand "Users" node
    And I follow "Groups"
    When I add "Student 1" user to "Group 1" group members
    And I add "Student 2" user to "Group 2" group members
    And I add "Student 3" user to "Group 1" group members
    And I add "Student 3" user to "Group 2" group members

  @javascript
  Scenario: When potential members is less than configured limit, view existing group memberships without search
    Given I set the field "groups" to "Group 3"
    And I press "Add/remove users"
    Then the "members" select box should contain "Student 1 (student1@example.com) (1)"
    And the "members" select box should contain "Student 2 (student2@example.com) (1)"
    And the "members" select box should contain "Student 3 (student3@example.com) (2)"
    And the "members" select box should contain "Student 4 (student4@example.com) (0)"
    When I set the field "addselect" to "Student 1 (student1@example.com) (1)"
    Then I should see "Group 1"

  @javascript
  Scenario: When potential members is less than configured limit, view existing group memberships with search
    Given I set the field "groups" to "Group 3"
    And I press "Add/remove users"
    Then the "members" select box should contain "Student 1 (student1@example.com) (1)"
    And the "members" select box should contain "Student 2 (student2@example.com) (1)"
    And the "members" select box should contain "Student 3 (student3@example.com) (2)"
    And the "members" select box should contain "Student 4 (student4@example.com) (0)"
    When I set the field "addselect_searchtext" to "3"
    Then the "members" select box should contain "Student 3 (student3@example.com) (2)"
    When I set the field "addselect" to "Student 3 (student3@example.com) (2)"
    Then I should see "Group 1"
    And I should see "Group 2"

  @javascript
  Scenario: When potential members is more than configured limit, view existing group memberships with search
    Given the following config values are set as admin:
      | maxusersperpage | 2 |
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I expand "Users" node
    And I follow "Groups"
    Given I set the field "groups" to "Group 3"
    And I press "Add/remove users"
    Then the "members" select box should contain "Too many users (4) to show"
    When I set the field "addselect_searchtext" to "3"
    Then the "members" select box should contain "Student 3 (student3@example.com) (2)"
    When I set the field "addselect" to "Student 3 (student3@example.com) (2)"
    Then I should see "Group 1"
    And I should see "Group 2"