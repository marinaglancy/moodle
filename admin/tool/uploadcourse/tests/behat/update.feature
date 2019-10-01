@tool @tool_uploadcourse @_file_upload
Feature: An admin can update courses using a CSV file
  In order to update courses using a CSV file
  As an admin
  I need to be able to upload a CSV file and navigate through the import process

  @javascript
  Scenario: Updating a course fullname
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Some random name | C1 | 0 |
    And I log in as "admin"
    And I navigate to "Courses > Upload courses" in site administration
    Given I upload "admin/tool/uploadcourse/tests/fixtures/courses.csv" file to "File" filemanager
    And I set the field "Upload mode" to "Only update existing courses"
    And I set the field "Update mode" to "Update with CSV data only"
    And I click on "Preview" "button"
    When I click on "Upload courses" "button"
    Then I should see "Course updated"
    And I should see "The course does not exist and creating course is not allowed"
    And I should see "Courses total: 3"
    And I should see "Courses updated: 1"
    And I should see "Courses created: 0"
    And I should see "Courses errors: 2"
    And I am on site homepage
    And I should see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"

  @javascript
  Scenario: Manager can use upload user tool to update courses in course category
    Given the following "users" exist:
      | username | firstname | lastname | email                  |
      | user1    | User      | 1        | user1@example.com |
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT1     | CAT3     |
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | CAT1     |
      | Course 2 | C2        | CAT2     |
      | Course 3 | C3        | CAT3     |
      | Course 4 | C4        | CAT3     |
    And the following "role assigns" exist:
      | user  | role    | contextlevel | reference |
      | user1 | manager | Category     | CAT1      |
    When I log in as "user1"
    And I am on course index
    And I follow "Cat 1"
    And I navigate to "Upload courses" in current page administration
    And I upload "admin/tool/uploadcourse/tests/fixtures/courses_manager2.csv" file to "File" filemanager
    And I set the field "Upload mode" to "Only update existing courses"
    And I set the field "Update mode" to "Update with CSV data only"
    And I click on "Preview" "button"
    # Course C1 is in "our" category but can not be moved to Cat 2 because current user can not manage it.
    Then I should see "No permission to upload courses in category: Cat 2" in the "C1" "table_row"
    # Course C2 can not be updated (no capability in "Cat 2" context).
    And I should see "Course with this shortname exists and you don't have permission to use upload course tool to update it" in the "C2" "table_row"
    # Course with short name "C5" does not exist.
    And I should see "The course does not exist and creating course is not allowed" in the "C5" "table_row"
    And I click on "Upload courses" "button"
    And I should see "Course updated"
    And I should see "Courses total: 5"
    And I should see "Courses updated: 2"
    And I should see "Courses errors: 3"
    And I am on course index
    And I follow "Cat 1"
    # Course C4 was moved from Cat 3 to Cat 1.
    And I should see "Course 4"
    And I should see "Course 1"
    And I follow "Cat 3"
    And I should see "Course 3"
    And I log out
