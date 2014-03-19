@mod @mod_feedback @block_recent_activity
Feature: New feedbacks are shown in recent activity report
  In order to see recently added feedbacks
  As a teacher
  I need to be able to view feedbacks in recent activity report

  @javascript
  Scenario: View feedbacks in recent activity report
    Given I log in as "admin"
    And I expand "Users" node
    And I set the following system permissions of "Non-editing teacher" role:
      | moodle/site:accessallgroups | Prevent |
    And I am on homepage
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Activity modules" node
    And I follow "Manage activities"
    And I click on "//a[@title=\"Show\"]" "xpath_element" in the "Feedback" "table_row"
    And I log out
    And the following "users" exist:
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
      | activity   | name                     | intro                     | course | idnumber   | groupmode |
      | feedback   | Common                   | Test feedback description | c1     | feedback1  | 0         |
      | feedback   | VisibleGroups            | Test feedback description | c1     | feedback2  | 2         |
      | feedback   | SeparateGroupsNoGrouping | Test feedback description | c1     | feedback3  | 1         |
    And the following "activities" exist:
      | activity | name                    | intro                     | course | idnumber  | groupmode | grouping |
      | feedback | SeparateGroupsGrouping1 | Test feedback description | c1     | feedback4 | 1         | GG1      |
# Teacher1 adds questions to feedbacks
    When I log in as "teacher1"
    And I follow "Course1"
    And I follow "Common"
    And I follow "Edit questions"
    And I set the following fields to these values:
      | id_typ | Multiple choice |
      | Question | Multiplechoicequestion |
      | Label    | Label    |
    And I set the field "id_values" to:
      """
      option1
      option2
      option3
      """
    And I press "Save question"
    And I follow "Course1"
    And I follow "VisibleGroups"
    And I follow "Edit questions"
    And I set the following fields to these values:
      | id_typ | Short text answer |
      | Question | Shorttextquestion |
      | Label    | Label    |
    And I press "Save question"
    And I follow "Course1"
    And I follow "SeparateGroupsNoGrouping"
    And I follow "Edit questions"
    And I set the following fields to these values:
      | id_typ | Short text answer |
      | Question | Shorttextquestion |
      | Label    | Label    |
    And I press "Save question"
    And I follow "Course1"
    And I follow "SeparateGroupsGrouping1"
    And I follow "Edit questions"
    And I set the following fields to these values:
      | id_typ | Short text answer |
      | Question | Shorttextquestion |
      | Label    | Label    |
    And I press "Save question"
    And I log out
# Student1 gives feedbacks
    And I log in as "student1"
    And I follow "Course1"
    And I follow "Common"
    And I follow "Answer the questions"
    And I click on "multichoice_1_2" "radio"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "VisibleGroups"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a1"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "SeparateGroupsNoGrouping"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a1"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "SeparateGroupsGrouping1"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a1"
    And I press "Submit your answers"
    And I press "Continue"
    And I open course recent activity report
    Then I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam1                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam1                     |
    And I log out
# Student2 gives feedbacks
    And I log in as "student2"
    And I follow "Course1"
    And I follow "Common"
    And I follow "Answer the questions"
    And I click on "multichoice_1_2" "radio"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "VisibleGroups"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a2"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "SeparateGroupsNoGrouping"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a2"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "SeparateGroupsGrouping1"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a2"
    And I press "Submit your answers"
    And I press "Continue"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam2                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam2                     |
    And I log out
# Student3 gives feedbacks
    And I log in as "student3"
    And I follow "Course1"
    And I follow "Common"
    And I follow "Answer the questions"
    And I click on "multichoice_1_2" "radio"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "VisibleGroups"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a3"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "SeparateGroupsNoGrouping"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a3"
    And I press "Submit your answers"
    And I press "Continue"
    And I follow "SeparateGroupsGrouping1"
    And I follow "Answer the questions"
    And I set the field "Shorttextquestion" to "a3"
    And I press "Submit your answers"
    And I press "Continue"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam3                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam3                     |
    And I log out
# Teacher1 views report
    And I log in as "teacher1"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
    And I log out
# Teacher2 views report
    And I log in as "teacher2"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam2                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam2                     |
    And I log out
# Teacher3 views report
    And I log in as "teacher3"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam2                     |
      | h3 | SeparateGroupsGrouping1  |
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
# Teacher1 views report
    And I log in as "teacher1"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
    And I log out
# Teacher2 views report
    And I log in as "teacher2"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam2                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam2                     |
    And I log out
# Teacher3 views report
    And I log in as "teacher3"
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      |    | Sam3                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam2                     |
    And I log out
