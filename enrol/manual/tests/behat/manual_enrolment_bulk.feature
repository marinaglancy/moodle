@enrol @enrol_manual
Feature: Teacher is able to bulk edit or delete manual enrolments
  In order to manage enrolments
  As a teacher
  I need to be able to bulk edit or delete manual enrolments

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |

  @javascript
  Scenario: Bulk delete self enrolments
    When I log in as "teacher1"
    And I follow "Course 1"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I set the field "Enrolment methods" to "Manual"
    And I press "Filter"
    And I click on "input[type=checkbox]" "css_element" in the "Student 1" "table_row"
    And I click on "input[type=checkbox]" "css_element" in the "Student 3" "table_row"
    And I set the field "bulkuserop" to "Delete selected user enrolments"
    And I press "Go"
    Then I should see "Student 1"
    And I should see "Student 3"
    And I should not see "Student 2"
    And I should see "Are you sure you want to delete these users enrolments?"
    And I press "Unenrol users"
    And I should not see "Student 1"
    And I should not see "Student 3"
    And I should see "Student 2"
    And I log out

  @javascript
  Scenario: Bulk edit self enrolments
    When I log in as "teacher1"
    And I follow "Course 1"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I set the field "Enrolment methods" to "Manual"
    And I press "Filter"
    And I click on "input[type=checkbox]" "css_element" in the "Student 1" "table_row"
    And I click on "input[type=checkbox]" "css_element" in the "Student 3" "table_row"
    And I set the field "bulkuserop" to "Edit selected user enrolments"
    And I press "Go"
    Then I should see "Student 1"
    And I should see "Student 3"
    And I should not see "Student 2"
    And I set the field "Alter status" to "Suspended"
    And I press "Save changes"
    And I click on "Edit" "link" in the "Student 1" "table_row"
    And the field "Status" matches value "Suspended"
    And I press "Cancel"
    And I click on "Edit" "link" in the "Student 2" "table_row"
    And the field "Status" matches value "Active"
    And I press "Cancel"
    And I click on "Edit" "link" in the "Student 3" "table_row"
    And the field "Status" matches value "Suspended"
    And I press "Cancel"
    And I log out
