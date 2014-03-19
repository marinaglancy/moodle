@mod @mod_forum @block_recent_activity
Feature: New forum posts in Recent activity block and report
  In order to know what's going on in the forums
  As a user
  I need to be able to view recent forum posts in Recent activity block and report

  @javascript
  Scenario: View new forum discussions and posts
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Sam1      | Student1 | student1@asd.com |
      | student2 | Sam2      | Student2 | student2@asd.com |
      | student3 | Sam3      | Student3 | student3@asd.com |
      | student4 | Sam4      | Student4 | student4@asd.com |
      | teacher1 | Terry1    | Teacher1 | teacher1@asd.com |
      | teacher2 | Terry2    | Teacher2 | teacher2@asd.com |
    And the following "courses" exist:
      | fullname  | shortname |
      | Course1   | c1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | c1     | student |
      | student2 | c1     | student |
      | student3 | c1     | student |
      | student4 | c1     | student |
      | teacher1 | c1     | teacher |
      | teacher2 | c1     | teacher |
    And the following "groups" exist:
      | name    | idnumber | course |
      | Group 1 | G1       | c1 |
      | Group 2 | G2       | c1 |
    And the following "groupings" exist:
      | name    | idnumber | course |
      | Grouping 1 | GG1       | c1 |
      | Grouping 2 | GG2       | c1 |
      | Grouping 3 | GG3       | c1 |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |
      | student4 | G1    |
      | teacher1 | G1    |
      | teacher1 | G2    |
    And the following "grouping groups" exist:
      | grouping | group |
      | GG1      | G1    |
      | GG2      | G2    |
      | GG3      | G1    |
      | GG3      | G2    |
    And the following "activities" exist:
      | activity | name                     | intro                  | course | idnumber | groupmode |
      | forum    | EverybodyAccess          | Test forum description | c1     | forum1   | 0         |
      | forum    | VisibleGroups            | Test forum description | c1     | forum2   | 2         |
      | forum    | SeparateGroupsNoGrouping | Test forum description | c1     | forum3   | 1         |
    And the following "activities" exist:
      | activity | name                    | intro                  | course | idnumber | groupmode | grouping |
      | forum    | SeparateGroupsGrouping2 | Test forum description | c1     | forum3   | 1         | GG2      |
      | forum    | SeparateGroupsGrouping3 | Test forum description | c1     | forum3   | 1         | GG3      |
# Reset the recent activity block contents for students and teachers
    And I log in as "student1"
    And I follow "Course1"
    And I log out
    And I log in as "student2"
    And I follow "Course1"
    And I log out
    And I log in as "student3"
    And I follow "Course1"
    And I log out
    And I log in as "student4"
    And I follow "Course1"
    And I log out
    And I log in as "teacher1"
    And I follow "Course1"
    And I log out
    And I log in as "teacher2"
    And I follow "Course1"
    And I log out
# teacher1
    When I log in as "teacher1"
    And I follow "Course1"
    And I add a new discussion to "EverybodyAccess" forum with:
      | Subject | SubjEverybodyAccess |
      | Message | message             |
    And I add a new discussion to "VisibleGroups" forum with:
      | Subject | VisNoGroup |
      | Message | message    |
    And I add a new discussion to "VisibleGroups" forum with:
      | Subject | VisGroup1 |
      | Message | message   |
      | Group   | Group 1   |
    And I add a new discussion to "SeparateGroupsNoGrouping" forum with:
      | Subject | SepNoGroups |
      | Message | message     |
    And I add a new discussion to "SeparateGroupsNoGrouping" forum with:
      | Subject | SepGroup2 |
      | Message | message   |
      | Group   | Group 2   |
    And I add a new discussion to "SeparateGroupsGrouping2" forum with:
      | Subject | SepGrouping2NoGroup |
      | Message | message             |
    And I add a new discussion to "SeparateGroupsGrouping3" forum with:
      | Subject | SepGrouping3Group2 |
      | Message | message            |
      | Group   | Group 2            |
    And I follow "Course1"
    And I should see in recent activity block under "New forum posts":
      | .head | Terry1              |
      | .info | SubjEverybodyAccess |
      | .head | Terry1              |
      | .info | VisNoGroup          |
      | .head | Terry1              |
      | .info | VisGroup1           |
      | .head | Terry1              |
      | .info | SepNoGroups         |
      | .head | Terry1              |
      | .info | SepGroup2           |
      | .head | Terry1              |
      | .info | SepGrouping2NoGroup |
      | .head | Terry1              |
      | .info | SepGrouping3Group2  |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | EverybodyAccess          |        |
      |    | SubjEverybodyAccess      | Terry1 |
      | h3 | VisibleGroups            |        |
      |    | VisNoGroup               | Terry1 |
      |    | VisGroup1                | Terry1 |
      | h3 | SeparateGroupsNoGrouping |        |
      |    | SepNoGroups              | Terry1 |
      |    | SepGroup2                | Terry1 |
      | h3 | SeparateGroupsGrouping2  |        |
      |    | SepGrouping2NoGroup      | Terry1 |
      | h3 | SeparateGroupsGrouping3  |        |
      |    | SepGrouping3Group2       | Terry1 |
    And I log out
# teacher2
    And I log in as "teacher2"
    And I follow "Course1"
    And I should see in recent activity block under "New forum posts":
      | .head | Terry1              |
      | .info | SubjEverybodyAccess |
      | .head | Terry1              |
      | .info | VisNoGroup          |
      | .head | Terry1              |
      | .info | VisGroup1           |
      | .head | Terry1              |
      | .info | SepNoGroups         |
      | .head | Terry1              |
      | .info | SepGroup2           |
      | .head | Terry1              |
      | .info | SepGrouping2NoGroup |
      | .head | Terry1              |
      | .info | SepGrouping3Group2  |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | EverybodyAccess          |        |
      |    | SubjEverybodyAccess      | Terry1 |
      | h3 | VisibleGroups            |        |
      |    | VisNoGroup               | Terry1 |
      |    | VisGroup1                | Terry1 |
      | h3 | SeparateGroupsNoGrouping |        |
      |    | SepNoGroups              | Terry1 |
      |    | SepGroup2                | Terry1 |
      | h3 | SeparateGroupsGrouping2  |        |
      |    | SepGrouping2NoGroup      | Terry1 |
      | h3 | SeparateGroupsGrouping3  |        |
      |    | SepGrouping3Group2       | Terry1 |
    And I log out
# student1
    And I log in as "student1"
    And I follow "Course1"
    And I should see in recent activity block under "New forum posts":
      | .head | Terry1              |
      | .info | SubjEverybodyAccess |
      | .head | Terry1              |
      | .info | VisNoGroup          |
      | .head | Terry1              |
      | .info | VisGroup1           |
      | .head | Terry1              |
      | .info | SepNoGroups         |
      | .head | Terry1              |
      | .info | SepGrouping2NoGroup |
    And I reply "VisGroup1" post from "VisibleGroups" forum with:
      | Subject | Response1 |
      | Message | Response1 |
    And I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | EverybodyAccess          |        |
      |    | SubjEverybodyAccess      | Terry1 |
      | h3 | VisibleGroups            |        |
      |    | VisNoGroup               | Terry1 |
      |    | VisGroup1                | Terry1 |
      |    | Response1                | Sam1   |
      | h3 | SeparateGroupsNoGrouping |        |
      |    | SepNoGroups              | Terry1 |
      | h3 | SeparateGroupsGrouping2  |        |
      |    | SepGrouping2NoGroup      | Terry1 |
      | h3 | SeparateGroupsGrouping3  |        |
    And I log out
# student2
    And I log in as "student2"
    And I follow "Course1"
    And I should see in recent activity block under "New forum posts":
      | .head | Terry1              |
      | .info | SubjEverybodyAccess |
      | .head | Terry1              |
      | .info | VisNoGroup          |
      | .head | Terry1              |
      | .info | VisGroup1           |
      | .head | Terry1              |
      | .info | SepNoGroups         |
      | .head | Terry1              |
      | .info | SepGroup2           |
      | .head | Terry1              |
      | .info | SepGrouping2NoGroup |
      | .head | Terry1              |
      | .info | SepGrouping3Group2  |
      | .head | Sam1                |
      | .info | Response1           |
    And I reply "SepGroup2" post from "SeparateGroupsNoGrouping" forum with:
      | Subject | Response2 |
      | Message | Response2 |
    When I follow "Course1"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | EverybodyAccess          |        |
      |    | SubjEverybodyAccess      | Terry1 |
      | h3 | VisibleGroups            |        |
      |    | VisNoGroup               | Terry1 |
      |    | VisGroup1                | Terry1 |
      |    | Response1                | Sam1   |
      | h3 | SeparateGroupsNoGrouping |        |
      |    | SepNoGroups              | Terry1 |
      |    | SepGroup2                | Terry1 |
      |    | Response2                | Sam2   |
      | h3 | SeparateGroupsGrouping2  |        |
      |    | SepGrouping2NoGroup      | Terry1 |
      | h3 | SeparateGroupsGrouping3  |        |
      |    | SepGrouping3Group2       | Terry1 |
    And I follow "Course1"
    And I should see "Response2" in the "Recent activity" "block"
    And I log out
# student3
    And I log in as "student3"
    And I follow "Course1"
    And I should see in recent activity block under "New forum posts":
      | .head | Terry1              |
      | .info | SubjEverybodyAccess |
      | .head | Terry1              |
      | .info | VisNoGroup          |
      | .head | Terry1              |
      | .info | VisGroup1           |
      | .head | Terry1              |
      | .info | SepNoGroups         |
      | .head | Terry1              |
      | .info | SepGrouping2NoGroup |
      | .head | Sam1                |
      | .info | Response1           |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | EverybodyAccess          |        |
      |    | SubjEverybodyAccess      | Terry1 |
      | h3 | VisibleGroups            |        |
      |    | VisNoGroup               | Terry1 |
      |    | VisGroup1                | Terry1 |
      |    | Response1                | Sam1   |
      | h3 | SeparateGroupsNoGrouping |        |
      |    | SepNoGroups              | Terry1 |
      | h3 | SeparateGroupsGrouping2  |        |
      |    | SepGrouping2NoGroup      | Terry1 |
      | h3 | SeparateGroupsGrouping3  |        |
    And I log out
# student1
    And I log in as "student1"
    And I follow "Course1"
    And I should see in recent activity block under "New forum posts":
      | .head | Sam1                |
      | .info | Response1           |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | EverybodyAccess          |        |
      |    | SubjEverybodyAccess      | Terry1 |
      | h3 | VisibleGroups            |        |
      |    | VisNoGroup               | Terry1 |
      |    | VisGroup1                | Terry1 |
      |    | Response1                | Sam1   |
      | h3 | SeparateGroupsNoGrouping |        |
      |    | SepNoGroups              | Terry1 |
      | h3 | SeparateGroupsGrouping2  |        |
      |    | SepGrouping2NoGroup      | Terry1 |
      | h3 | SeparateGroupsGrouping3  |        |
    And I log out
# Enable group members only
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable group members only | 1 |
    And I am on homepage
    And I follow "Course1"
    And I follow "SeparateGroupsGrouping2"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Available for group members only | 1 |
    And I press "Save and return to course"
    And I log out
# student4 can not see posts in forum SeparateGroupsGrouping2 now
    And I log in as "student4"
    And I follow "Course1"
    And I should not see "SepGrouping2NoGroup" in the "Recent activity" "block"
    And I open course recent activity report
    And I should not see "SepGrouping2NoGroup" in the ".region-content" "css_element"
    And I log out
