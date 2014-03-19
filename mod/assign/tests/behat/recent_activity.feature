@mod @mod_assign @block_recent_activity
Feature: New assignment submissions in recent activity block
  In order to see recently submitted assignments 
  As a teacher
  I need to be able to view recent assignment submissions in Recent activity block and report

  @javascript
  Scenario: View assignment submissions in recent activity block
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
      | activity | name                     | intro                   | course | idnumber | groupmode | assignsubmission_onlinetext_enabled |
      | assign   | Common                   | Test assign description | c1     | assign1  | 0         | 1                                   |
      | assign   | VisibleGroups            | Test assign description | c1     | assign2  | 2         | 1                                   |
      | assign   | SeparateGroupsNoGrouping | Test assign description | c1     | assign3  | 1         | 1                                   |
    And the following "activities" exist:
      | activity | name                    | intro                   | course | idnumber | groupmode | grouping | assignsubmission_onlinetext_enabled |
      | assign   | SeparateGroupsGrouping1 | Test assign description | c1     | assign4  | 1         | GG1      | 1                                   |
    And I log in as "admin"
    And I expand "Users" node
    And I set the following system permissions of "Non-editing teacher" role:
      | moodle/site:accessallgroups | Prevent |
    And I log out
# Reset the recent activity block contents for students and teachers
    And I log in as "student1"
    And I follow "Course1"
    And I log out
    And I log in as "student2"
    And I follow "Course1"
    And I log out
    And I log in as "teacher1"
    And I follow "Course1"
    And I log out
    And I log in as "teacher2"
    And I follow "Course1"
    And I log out
    And I log in as "student3"
    And I follow "Course1"
    And I log out
    And I log in as "teacher3"
    And I follow "Course1"
    And I log out
# Student 1 (group 1, grouping 1)
    When I log in as "student1"
    And I follow "Course1"
    Then I should see nothing in recent activity block
    And I follow "Common"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | First Submission of student1 |
    And I press "Save changes"
    And I follow "Course1"
    And I follow "VisibleGroups"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | First Submission of student1 |
    And I press "Save changes"
    And I follow "Course1"
    And I follow "SeparateGroupsNoGrouping"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | First Submission of student1 |
    And I press "Save changes"
    And I follow "Course1"
    And I follow "SeparateGroupsGrouping1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | First Submission of student1 |
    And I press "Save changes"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam1                     |
      | .info | Common                   |
      | .head | Sam1                     |
      | .info | VisibleGroups            |
      | .head | Sam1                     |
      | .info | SeparateGroupsNoGrouping |
      | .head | Sam1                     |
      | .info | SeparateGroupsGrouping1  |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam1                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam1                     |
    And I log out
# Make a delay between consequtive logins of the same user, otherwise lastaccess time might not be updated.
    And I wait "60" seconds
# Teacher 1 (can access all groups)
    And I log in as "teacher1"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam1                     |
      | .info | Common                   |
      | .head | Sam1                     |
      | .info | VisibleGroups            |
      | .head | Sam1                     |
      | .info | SeparateGroupsNoGrouping |
      | .head | Sam1                     |
      | .info | SeparateGroupsGrouping1  |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam1                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam1                     |
    And I log out
# Teacher 2 (group 2, grouping 1)
    And I log in as "teacher2"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam1                     |
      | .info | Common                   |
      | .head | Sam1                     |
      | .info | VisibleGroups            |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      | h3 | SeparateGroupsNoGrouping |
      | h3 | SeparateGroupsGrouping1  |
    And I log out
# Teacher 3 (group 4 but not part of grouping 1)
    And I log in as "teacher3"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam1                     |
      | .info | Common                   |
      | .head | Sam1                     |
      | .info | VisibleGroups            |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      | h3 | SeparateGroupsNoGrouping |
      | h3 | SeparateGroupsGrouping1  |
    And I log out
# Student 2 (group 1 and 4, grouping 1)
    And I log in as "student2"
    And I follow "Course1"
    And I should see nothing in recent activity block
    And I follow "Common"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | First Submission of student2 |
    And I press "Save changes"
    And I follow "Course1"
    And I follow "VisibleGroups"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | First Submission of student2 |
    And I press "Save changes"
    And I follow "Course1"
    And I follow "SeparateGroupsNoGrouping"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | First Submission of student2 |
    And I press "Save changes"
    And I follow "Course1"
    And I follow "SeparateGroupsGrouping1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | First Submission of student2 |
    And I press "Save changes"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam2                     |
      | .info | Common                   |
      | .head | Sam2                     |
      | .info | VisibleGroups            |
      | .head | Sam2                     |
      | .info | SeparateGroupsNoGrouping |
      | .head | Sam2                     |
      | .info | SeparateGroupsGrouping1  |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam2                     |
      | h3 | VisibleGroups            |
      |    | Sam2                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam2                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam2                     |
    And I log out
# Teacher 1 (can access all groups)
    And I log in as "teacher1"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam2                     |
      | .info | Common                   |
      | .head | Sam2                     |
      | .info | VisibleGroups            |
      | .head | Sam2                     |
      | .info | SeparateGroupsNoGrouping |
      | .head | Sam2                     |
      | .info | SeparateGroupsGrouping1  |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      | h3 | SeparateGroupsNoGrouping |
      |    | Sam1                     |
      |    | Sam2                     |
      | h3 | SeparateGroupsGrouping1  |
      |    | Sam1                     |
      |    | Sam2                     |
    And I log out
# Teacher 2 (group 2, grouping 1)
    And I log in as "teacher2"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam2                     |
      | .info | Common                   |
      | .head | Sam2                     |
      | .info | VisibleGroups            |
      | .head | Sam2                     |
      | .info | SeparateGroupsNoGrouping |
      | .head | Sam2                     |
      | .info | SeparateGroupsGrouping1  |
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
# Teacher 3 (group 4 but not part of grouping 1)
    And I log in as "teacher3"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam2                     |
      | .info | Common                   |
      | .head | Sam2                     |
      | .info | VisibleGroups            |
      | .head | Sam2                     |
      | .info | SeparateGroupsNoGrouping |
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
    And I log out
# Admin allows students to see each other's submissions
    And I log in as "admin"
    And I set the following administration settings values:
      | Show recent submissions | 1 |
    And I log out
# Now student 3 can see submissions of other students
    And I log in as "student3"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | Assignments submitted:   |
      | .head | Sam1                     |
      | .info | Common                   |
      | .head | Sam1                     |
      | .info | VisibleGroups            |
      | .head | Sam2                     |
      | .info | Common                   |
      | .head | Sam2                     |
      | .info | VisibleGroups            |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | Common                   |
      |    | Sam1                     |
      |    | Sam2                     |
      | h3 | VisibleGroups            |
      |    | Sam1                     |
      |    | Sam2                     |
      | h3 | SeparateGroupsNoGrouping |
      | h3 | SeparateGroupsGrouping1  |
    And I log out
