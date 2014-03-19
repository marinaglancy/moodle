@mod @mod_quiz @block_recent_activity
Feature: New quiz attempts in recent activity report
  In order to see recent quiz attempts
  As a teacher
  I need to be able to view quiz attempts in recent activity report

  @javascript
  Scenario: View quiz attempts in recent activity report
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Sam1      | Student1 | student1@asd.com |
      | student2 | Sam2      | Student2 | student2@asd.com |
      | student3 | Sam3      | Student3 | student3@asd.com |
      | teacher1 | Terry1    | Teacher1 | teacher1@asd.com |
      | teacher2 | Terry2    | Teacher2 | teacher2@asd.com |
      | teacher3 | Terry3    | Teacher3 | teacher3@asd.com |
    And the following "courses" exist:
      | fullname  | shortname |
      | Course1   | c1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | c1     | student |
      | student2 | c1     | student |
      | student3 | c1     | student |
      | teacher1 | c1     | editingteacher |
      | teacher2 | c1     | teacher |
      | teacher3 | c1     | teacher |
    And the following "groups" exist:
      | name    | idnumber | course |
      | Group 1 | G1       | c1 |
      | Group 2 | G2       | c1 |
      | Group 4 | G4       | c1 |
    And the following "groupings" exist:
      | name    | idnumber | course |
      | Grouping 1 | GG1       | c1 |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |
      | teacher1 | G1    |
      | teacher2 | G2    |
      | student2 | G4    |
      | teacher3 | G4    |
    And the following "grouping groups" exist:
      | grouping | group |
      | GG1      | G1    |
      | GG1      | G2    |
    And the following "activities" exist:
      | activity | name                     | intro                 | course | idnumber | groupmode |
      | quiz     | Common                   | Test quiz description | c1     | quiz1    | 0         |
      | quiz     | VisibleGroups            | Test quiz description | c1     | quiz2    | 2         |
      | quiz     | SeparateGroupsNoGrouping | Test quiz description | c1     | quiz3    | 1         |
    And the following "activities" exist:
      | activity | name                    | intro                 | course | idnumber | groupmode | grouping |
      | quiz     | SeparateGroupsGrouping1 | Test quiz description | c1     | quiz4    | 1         | GG1      |
    And I log in as "admin"
    And I expand "Users" node
    And I set the following system permissions of "Non-editing teacher" role:
      | moodle/site:accessallgroups | Prevent |
    And I log out
# Teacher 1
    And I log in as "teacher1"
    And I follow "Course1"
    And I add a "True/False" question to the "Common" quiz with:
      | Question name | False question |
      | Question text | Sky is green   |
    And I follow "Course1"
    And I add a "True/False" question to the "VisibleGroups" quiz with:
      | Question name  | True question       |
      | Question text  | Sky is blue |
      | Correct answer | True |
    And I follow "Course1"
    And I add a "Short answer" question to the "SeparateGroupsNoGrouping" quiz with:
      | Question name | Flying       |
      | Question text | Can you fly? |
      | Answer 1      | no           |
      | fraction[0]   | 100%         |
    And I follow "Course1"
    And I add a "Numerical" question to the "SeparateGroupsGrouping1" quiz with:
      | Question name | Timestable |
      | Question text | 2x2        |
      | answer[0]     | 4          |
      | fraction[0]   | 100%       |
    And I log out
# Student 1
    And I log in as "student1"
    And I follow "Course1"
    And I attempt the quiz "Common" with:
      | True | 1 |
    And I follow "Course1"
    And I attempt the quiz "VisibleGroups" with:
      | False | 1 |
    And I follow "Course1"
    And I attempt the quiz "SeparateGroupsNoGrouping" with:
      | Answer | yes |
    And I follow "Course1"
    And I attempt the quiz "SeparateGroupsGrouping1" with:
      | Answer | 3 |
    And I log out
# Student 2
    When I log in as "student2"
    And I follow "Course1"
    And I attempt the quiz "Common" with:
      | False | 1 |
    And I follow "Course1"
    And I attempt the quiz "VisibleGroups" with:
      | True | 1 |
    And I follow "Course1"
    And I attempt the quiz "SeparateGroupsNoGrouping" with:
      | Answer | no |
    And I follow "Course1"
    And I attempt the quiz "SeparateGroupsGrouping1" with:
      | Answer | 4 |
    And I follow "Course1"
    And I open course recent activity report
    Then I should see in course recent activity report:
      | h3 | Common                   |      |
      |    | Attempt 1                | Sam2 |
      | h3 | VisibleGroups            |      |
      |    | Attempt 1                | Sam2 |
      | h3 | SeparateGroupsNoGrouping |      |
      |    | Attempt 1                | Sam2 |
      | h3 | SeparateGroupsGrouping1  |      |
      |    | Attempt 1                | Sam2 |
    And I log out
# Student 3
    And I log in as "student3"
    And I follow "Course1"
    And I attempt the quiz "Common" with:
      | True | 1 |
    And I follow "Course1"
    And I attempt the quiz "VisibleGroups" with:
      | True | 1 |
    And I follow "Course1"
    And I attempt the quiz "SeparateGroupsNoGrouping" with:
      | Answer | yes |
    And I follow "Course1"
    And I attempt the quiz "SeparateGroupsGrouping1" with:
      | Answer | 5 |
    And I log out
# Teacher 1
    And I log in as "teacher1"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | VisibleGroups            |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | SeparateGroupsNoGrouping |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | SeparateGroupsGrouping1  |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
    And I log out
# Teacher 2
    And I log in as "teacher2"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | VisibleGroups            |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | SeparateGroupsNoGrouping |      |
      |    | Attempt 1                | Sam2 |
      | h3 | SeparateGroupsGrouping1  |      |
      |    | Attempt 1                | Sam2 |
    And I log out
# Teacher 3
    And I log in as "teacher3"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | VisibleGroups            |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | SeparateGroupsNoGrouping |      |
      |    | Attempt 1                | Sam2 |
      | h3 | SeparateGroupsGrouping1  |      |
    And I log out
# Student 1
    And I log in as "student1"
    And I follow "Course1"
    And I attempt the quiz "Common" with:
      | False | 1 |
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 2                | Sam1 |
      | h3 | VisibleGroups            |      |
      |    | Attempt 1                | Sam1 |
      | h3 | SeparateGroupsNoGrouping |      |
      |    | Attempt 1                | Sam1 |
      | h3 | SeparateGroupsGrouping1  |      |
      |    | Attempt 1                | Sam1 |
    And I log out
# Enable group members only
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable group members only | 1 |
    And I am on homepage
    And I follow "Course1"
    And I follow "SeparateGroupsGrouping1"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Available for group members only | 1 |
    And I press "Save and return to course"
    And I log out
# Teacher 1
    And I log in as "teacher1"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      |    | Attempt 2                | Sam1 |
      | h3 | VisibleGroups            |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | SeparateGroupsNoGrouping |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | SeparateGroupsGrouping1  |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
    And I log out
# Teacher 2
    And I log in as "teacher2"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      |    | Attempt 2                | Sam1 |
      | h3 | VisibleGroups            |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | SeparateGroupsNoGrouping |      |
      |    | Attempt 1                | Sam2 |
      | h3 | SeparateGroupsGrouping1  |      |
      |    | Attempt 1                | Sam2 |
    And I log out
# Teacher 3
    And I log in as "teacher3"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      |    | Attempt 2                | Sam1 |
      | h3 | VisibleGroups            |      |
      |    | Attempt 1                | Sam1 |
      |    | Attempt 1                | Sam2 |
      |    | Attempt 1                | Sam3 |
      | h3 | SeparateGroupsNoGrouping |      |
      |    | Attempt 1                | Sam2 |
    And I log out
