@core @core_grades
Feature: View gradebook when scales are used
  In order to -----
  As an teacher
  I need to --------

  Background:
    And I log in as "admin"
    Given I set the following administration settings values:
      | grade_report_showranges    | 1 |
      | grade_aggregations_visible | Mean of grades,Weighted mean of grades,Simple weighted mean of grades,Mean of grades (with extra credits),Median of grades,Lowest grade,Highest grade,Mode of grades,Sum of grades |
    And I log out
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email            | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@asd.com | t1       |
      | student1 | Student   | 1        | student1@asd.com | s1       |
      | student2 | Student   | 2        | student2@asd.com | s2       |
      | student3 | Student   | 3        | student3@asd.com | s3       |
      | student4 | Student   | 4        | student4@asd.com | s4       |
      | student5 | Student   | 5        | student5@asd.com | s5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
    Given the following "scales" exist:
      | name        | scale     |
      | Letterscale | F,D,C,B,A |
    Given the following "grade categories" exist:
      | fullname       | course |
      | Sub category 1 | C1     |
    And the following "activities" exist:
      | activity | course | idnumber | name                | intro             | grade       | gradecategory  |
      | assign   | C1     | a1       | Test assignment one | Submit something! | Letterscale | Sub category 1 |
    And I log in as "teacher1"
    And I follow "Course 1"
    When I follow "Test assignment one"
    When I follow "View/grade all submissions"
    And I click on "Grade Student 1" "link" in the "Student 1" "table_row"
    Given I set the field "Grade" to "A"
    And I press "Save and show next"
    Given I set the field "Grade" to "B"
    And I press "Save and show next"
    Given I set the field "Grade" to "C"
    And I press "Save and show next"
    Given I set the field "Grade" to "D"
    And I press "Save and show next"
    Given I set the field "Grade" to "F"
    And I press "Save changes"
    And I follow "Course 1"
    And I follow "Grades"
    And I turn editing mode on

  @javascript
  Scenario: Test displaying scales in gradebook in aggregation method Sum of grades
    And I follow "Edit   Course 1"
    And I set the field "Aggregation" to "Sum of grades"
    And I press "Save changes"
    And I follow "Edit   Sub category 1"
    And I set the field "Aggregation" to "Sum of grades"
    And I set the field "Category name" to "Sub category (Sum of grades)"
    And I press "Save changes"
    And I turn editing mode off
    And I save a screenshot
    And I set the field "jump" to "Simple view"
    And I save a screenshot
    When I follow "User report"
    Given I set the field "Select all or one user" to "All users"
    And I click on "Select all or one user" "select"
    And I save a screenshot
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Grades"
    And I save a screenshot

  @javascript
  Scenario Outline: Test displaying scales in gradebook in all other aggregation methods
    And I follow "Edit   Course 1"
    And I set the field "Aggregation" to "<aggregation>"
    And I press "Save changes"
    And I follow "Edit   Sub category 1"
    And I expand all fieldsets
    And I set the field "Aggregation" to "<aggregation>"
    And I set the field "Category name" to "Sub category (<aggregation>)"
    And I set the field "Maximum grade" to "5"
    And I set the field "Minimum grade" to "1"
    And I press "Save changes"
    And I turn editing mode off
    And I save a screenshot
    And I set the field "jump" to "Simple view"
    And I save a screenshot
    When I follow "User report"
    Given I set the field "Select all or one user" to "All users"
    And I click on "Select all or one user" "select"
    And I save a screenshot
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Grades"
    And I save a screenshot

  Examples:
      | aggregation                         |
      | Mean of grades                      |
      | Weighted mean of grades             |
      | Simple weighted mean of grades      |
      | Mean of grades (with extra credits) |
      | Median of grades                    |
      | Lowest grade                        |
      | Highest grade                       |
      | Mode of grades                      |
