@core @core_grades @xxx
Feature: Rounding in the gradebook
  In order to
  As a teacher
  I need to

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email            | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | t1       |
      | student1 | Student | 1 | student1@example.com | s1                |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following administration settings values:
      |  grade_displaytype | Letter |
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I navigate to "Grades" node in "Course administration"
    And I follow "Letters"
    And I follow "Edit grade letters"

  Scenario Outline: xxx
    When I set the following fields to these values:
      | override               | 1  |
      | Grade letter 5         | B- |
      | gradeboundary5         | 60 |
      | Grade letter 6         | C+ |
      | gradeboundary6         | <v> |
      | Grade letter 7         | C |
      | gradeboundary7         | 54 |
      | gradeboundary8         | 52 |
      | gradeboundary9         | 50 |
      | gradeboundary10        | 45 |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
    And I follow "Test assignment name"
    And I follow "View/grade all submissions"
    And I click on "Grade Student 1" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | Grade | <v> |
    And I press "Save changes"
    And I navigate to "Grades" node in "Course administration"
    And "C+" "text" should exist in the "Student 1" "table_row"
    And I log out

  Examples:
    | v  |
    | 56 |
    | 57 |