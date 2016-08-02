@repository @repository_local @_file_upload
Feature: Server files repository lists the files used in the system
  In order to re-use files
  As a teacher
  I need to use again the files that are used in current or other courses

  @javascript
  Scenario: Add files recently uploaded
    Given the following "categories" exist:
      | name  | category | idnumber | visible |
      | Cat 1 | 0        | CAT1     | 1       |
      | Cat 2 | 0        | CAT2     | 0       |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | CAT1 |
      | Course 2 | C2 | CAT2 |
      | Course 3 | C3 | CAT1 |
      | Course 4 | C4 | CAT2 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | teacher1 | C1 | editingteacher |
      | student1 | C2 | student |
      | teacher1 | C2 | editingteacher |
    And the following "activities" exist:
      | activity   | name  | intro                         | course | idnumber    |
      | resource   | file1 | Test resource description     | C1     | resource1   |
      | resource   | file2 | Test resource description     | C2     | resource2   |
      | resource   | file3 | Test resource description     | C3     | resource3   |
      | resource   | file4 | Test resource description     | C4     | resource4   |
    When I log in as "teacher"
    And I should see "sfd"
    And I follow "Courses"
    And I follow "Cat 1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Folder" to section "0"
    And I set the following fields to these values:
      | Name | Folder resource |
      | Description | The description |

    And I open "Folder 1" folder from "Files" filemanager