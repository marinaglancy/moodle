@tool @tool_uploadcourse @_file_upload
Feature: An admin can create courses using a CSV file
  In order to create courses using a CSV file
  As an admin
  I need to be able to upload a CSV file and navigate through the import process

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | First course | C1 | 0 |
    And I log in as "admin"
    And I navigate to "Courses > Upload courses" in site administration

  @javascript
  Scenario: Creation of unexisting courses
    Given I upload "admin/tool/uploadcourse/tests/fixtures/courses.csv" file to "File" filemanager
    And I click on "Preview" "button"
    When I click on "Upload courses" "button"
    Then I should see "The course exists and update is not allowed"
    And I should see "Course created"
    And I should see "Courses total: 3"
    And I should see "Courses created: 2"
    And I should see "Courses errors: 1"
    And I am on site homepage
    And I should see "Course 2"
    And I should see "Course 3"

  @javascript
  Scenario: Creation of existing courses
    Given I upload "admin/tool/uploadcourse/tests/fixtures/courses.csv" file to "File" filemanager
    And I set the field "Upload mode" to "Create all, increment shortname if needed"
    And I click on "Preview" "button"
    When I click on "Upload courses" "button"
    Then I should see "Course created"
    And I should see "Course shortname incremented C1 -> C2"
    And I should see "Course shortname incremented C2 -> C3"
    And I should see "Course shortname incremented C3 -> C4"
    And I should see "Courses total: 3"
    And I should see "Courses created: 3"
    And I should see "Courses errors: 0"
    And I am on site homepage
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

  @javascript
  Scenario: Manager can use upload user tool in course category
    Given the following "users" exist:
      | username | firstname | lastname | email                  |
      | user1    | User      | 1        | user1@example.com |
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT1     | CAT3     |
    And the following "role assigns" exist:
      | user  | role    | contextlevel | reference |
      | user1 | manager | Category     | CAT1      |
    When I log out
    And I log in as "user1"
    And I am on course index
    And I follow "Cat 1"
    And I navigate to "Upload courses" in current page administration
    And I upload "admin/tool/uploadcourse/tests/fixtures/courses_manager1.csv" file to "File" filemanager
    And I click on "Preview" "button"
    Then I should see "The course exists and update is not allowed" in the "C1" "table_row"
    And I should see "No permission to upload courses in category: Cat 2" in the "C2" "table_row"
    And I set the field "Course category" to "Cat 1 / Cat 3"
    And I click on "Upload courses" "button"
    And I should see "Course created"
    And I should see "Courses total: 5"
    And I should see "Courses created: 3"
    And I should see "Courses errors: 2"
    And I am on course index
    And I follow "Cat 1"
    And I should see "Course 4"
    And I follow "Cat 3"
    And I should see "Course 5"
    # Course 3 did not have category specified in CSV file and it was uploaded to the default category.
    And I should see "Course 3"
    And I log out
