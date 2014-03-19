@mod @mod_workshop @block_recent_activity
Feature: New workshop submissions in Recent activity block and report
  In order to know who has submitted workshop
  As a teacher
  I need to be able to view recent submissions in Recent activity block and report

  @javascript
  Scenario: View new workshop submissions and assessments
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Sam1      | Student1 | student1@asd.com |
      | student2 | Sam2      | Student2 | student2@asd.com |
      | student3 | Sam3      | Student3 | student3@asd.com |
      | student4 | Sam4      | Student4 | student4@asd.com |
      | teacher1 | Terry1    | Teacher1 | teacher1@asd.com |
      | teacher2 | Terry2    | Teacher2 | teacher2@asd.com |
      | teacher3 | Terry3    | Teacher3 | teacher3@asd.com |
    And the following "courses" exist:
      | fullname  | shortname |
      | Course1   | c1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | c1     | student        |
      | student2 | c1     | student        |
      | student3 | c1     | student        |
      | student4 | c1     | student        |
      | teacher1 | c1     | editingteacher |
      | teacher2 | c1     | teacher        |
      | teacher3 | c1     | teacher        |
    And the following "groups" exist:
      | name    | idnumber | course |
      | Group 1 | G1       | c1 |
      | Group 2 | G2       | c1 |
      | Group 4 | G4       | c1 |
    And the following "groupings" exist:
      | name    | idnumber | course |
      | Grouping 1 | GG1       | c1 |
      | Grouping 2 | GG2       | c1 |
      | Grouping 3 | GG3       | c1 |
    And the following "grouping groups" exist:
      | grouping | group |
      | GG1      | G1    |
      | GG2      | G2    |
      | GG3      | G1    |
      | GG3      | G2    |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |
      | student2 | G4    |
      | student4 | G1    |
      | teacher1 | G1    |
      | teacher2 | G1    |
      | teacher3 | G4    |
    And the following "activities" exist:
      | activity | name                     | intro                  | course | idnumber | groupmode |
      | workshop    | EverybodyAccess          | Test workshop description | c1     | workshop1   | 0         |
      | workshop    | VisibleGroups            | Test workshop description | c1     | workshop2   | 2         |
      | workshop    | SeparateGroupsNoGrouping | Test workshop description | c1     | workshop3   | 1         |
    And the following "activities" exist:
      | activity | name                    | intro                  | course | idnumber | groupmode | grouping |
      | workshop    | SeparateGroupsGrouping2 | Test workshop description | c1     | workshop3   | 1         | GG2      |
      | workshop    | SeparateGroupsGrouping3 | Test workshop description | c1     | workshop3   | 1         | GG3      |
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
    And I log in as "student4"
    And I follow "Course1"
    And I log out
    And I log in as "teacher1"
    And I follow "Course1"
    And I log out
    And I log in as "teacher2"
    And I follow "Course1"
    And I log out
    And I log in as "teacher3"
    And I follow "Course1"
    And I log out
# teacher1
    And I log in as "teacher1"
    And I follow "Course1"
    And I edit assessment form in workshop "EverybodyAccess" as:"
      | id_description__idx_0_editor | Aspect1 |
      | id_description__idx_1_editor |         |
      | id_description__idx_2_editor |         |
    And I change phase in workshop "EverybodyAccess" to "Submission phase"
    And I follow "Course1"
    And I change phase in workshop "VisibleGroups" to "Submission phase"
    And I follow "Course1"
    And I change phase in workshop "SeparateGroupsNoGrouping" to "Submission phase"
    And I follow "Course1"
    And I change phase in workshop "SeparateGroupsGrouping2" to "Submission phase"
    And I follow "Course1"
    And I change phase in workshop "SeparateGroupsGrouping3" to "Submission phase"
    And I log out
# student1
    And I log in as "student1"
    And I follow "Course1"
    And I add a submission in workshop "EverybodyAccess" as:"
      | Title              | Submission11  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I add a submission in workshop "VisibleGroups" as:"
      | Title              | Submission12  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I add a submission in workshop "SeparateGroupsNoGrouping" as:"
      | Title              | Submission13  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I add a submission in workshop "SeparateGroupsGrouping2" as:"
      | Title              | Submission14  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I add a submission in workshop "SeparateGroupsGrouping3" as:"
      | Title              | Submission15  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I save a screenshot as "ss01_student1_b"
    Then I should see in recent activity block:
      | h3    | Workshop submissions: |
      | .head | Sam1                  |
      | .info | Submission11          |
      | .head | Sam1                  |
      | .info | Submission12          |
      | .head | Sam1                  |
      | .info | Submission13          |
      | .head | Sam1                  |
      | .info | Submission14          |
      | .head | Sam1                  |
      | .info | Submission15          |
    And I open course recent activity report
    And I save a screenshot as "ss01_student1_r"
    Then I should see in course recent activity report:
      | h3 | EverybodyAccess          |               |      |
      |    | Submission11             | Submission by | Sam1 |
      | h3 | VisibleGroups            |               |      |
      |    | Submission12             | Submission by | Sam1 |
      | h3 | SeparateGroupsNoGrouping |               |      |
      |    | Submission13             | Submission by | Sam1 |
      | h3 | SeparateGroupsGrouping2  |               |      |
      |    | Submission14             | Submission by | Sam1 |
      | h3 | SeparateGroupsGrouping3  |               |      |
      |    | Submission15             | Submission by | Sam1 |
    And I log out
# student2
    And I log in as "student2"
    And I follow "Course1"
    And I add a submission in workshop "EverybodyAccess" as:"
      | Title              | Submission21  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I add a submission in workshop "VisibleGroups" as:"
      | Title              | Submission22  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I add a submission in workshop "SeparateGroupsNoGrouping" as:"
      | Title              | Submission23  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I add a submission in workshop "SeparateGroupsGrouping2" as:"
      | Title              | Submission24  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I add a submission in workshop "SeparateGroupsGrouping3" as:"
      | Title              | Submission25  |
      | Submission content | Some content  |
    And I follow "Course1"
    And I save a screenshot as "ss02_student2_b"
    Then I should see in recent activity block:
      | h3    | Workshop submissions: |
      | .head | Sam2                  |
      | .info | Submission21          |
      | .head | Sam2                  |
      | .info | Submission22          |
      | .head | Sam2                  |
      | .info | Submission23          |
      | .head | Sam2                  |
      | .info | Submission24          |
      | .head | Sam2                  |
      | .info | Submission25          |
    And I open course recent activity report
    And I save a screenshot as "ss02_student2_r"
    Then I should see in course recent activity report:
      | h3 | EverybodyAccess          |               |      |
      |    | Submission21             | Submission by | Sam2 |
      | h3 | VisibleGroups            |               |      |
      |    | Submission22             | Submission by | Sam2 |
      | h3 | SeparateGroupsNoGrouping |               |      |
      |    | Submission23             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping2  |               |      |
      |    | Submission24             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping3  |               |      |
      |    | Submission25             | Submission by | Sam2 |
    And I log out
# teacher1
    And I log in as "teacher1"
    And I follow "Course1"
    And I save a screenshot as "ss03_teacher1_b"
    Then I should see in recent activity block:
      | h3    | Workshop submissions: |
      | .head | Sam1                  |
      | .info | Submission11          |
      | .head | Sam1                  |
      | .info | Submission12          |
      | .head | Sam1                  |
      | .info | Submission13          |
      | .head | Sam1                  |
      | .info | Submission14          |
      | .head | Sam1                  |
      | .info | Submission15          |
      | .head | Sam2                  |
      | .info | Submission21          |
      | .head | Sam2                  |
      | .info | Submission22          |
      | .head | Sam2                  |
      | .info | Submission23          |
      | .head | Sam2                  |
      | .info | Submission24          |
      | .head | Sam2                  |
      | .info | Submission25          |
    And I open course recent activity report
    And I save a screenshot as "ss03_teacher1_r"
    Then I should see in course recent activity report:
      | h3 | EverybodyAccess          |               |      |
      |    | Submission11             | Submission by | Sam1 |
      |    | Submission21             | Submission by | Sam2 |
      | h3 | VisibleGroups            |               |      |
      |    | Submission12             | Submission by | Sam1 |
      |    | Submission22             | Submission by | Sam2 |
      | h3 | SeparateGroupsNoGrouping |               |      |
      |    | Submission13             | Submission by | Sam1 |
      |    | Submission23             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping2  |               |      |
      |    | Submission14             | Submission by | Sam1 |
      |    | Submission24             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping3  |               |      |
      |    | Submission15             | Submission by | Sam1 |
      |    | Submission25             | Submission by | Sam2 |
    And I follow "Course1"
    And I allocate submissions in workshop "EverybodyAccess" as:"
      | Participant   | Reviewer      |
      | Sam1 Student1 | Sam2 Student2 |
      | Sam2 Student2 | Sam1 Student1 |
    And I change phase in workshop "EverybodyAccess" to "Assessment phase"
    And I log out
# student2
    And I log in as "student2"
    And I follow "Course1"
    When I assess submission "Sam1" in workshop "EverybodyAccess" as:"
      | grade__idx_0            | 8 / 10    |
      | peercomment__idx_0      |           |
      | Feedback for the author | Good work |
    And I follow "Course1"
    And I save a screenshot as "ss04_student2_b"
    Then I should see in recent activity block:
      | h3    | Workshop submissions: |
      | .head | Sam2                  |
      | .info | Submission21          |
      | .head | Sam2                  |
      | .info | Submission22          |
      | .head | Sam2                  |
      | .info | Submission23          |
      | .head | Sam2                  |
      | .info | Submission24          |
      | .head | Sam2                  |
      | .info | Submission25          |
      | h3    | Workshop assessments: |
      | .head | Sam2                  |
      | .info | Submission11          |
    And I open course recent activity report
    And I save a screenshot as "ss04_student2_r"
    Then I should see in course recent activity report:
      | h3 | EverybodyAccess          |               |      |
      |    | Submission21             | Submission by | Sam2 |
      |    | Submission11             | Assessment by | Sam2 |
      | h3 | VisibleGroups            |               |      |
      |    | Submission22             | Submission by | Sam2 |
      | h3 | SeparateGroupsNoGrouping |               |      |
      |    | Submission23             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping2  |               |      |
      |    | Submission24             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping3  |               |      |
      |    | Submission25             | Submission by | Sam2 |
    And I log out
# student4
    And I log in as "student4"
    And I follow "Course1"
    And I save a screenshot as "ss05_student4_b"
    Then I should see nothing in recent activity block
    And I open course recent activity report
    And I save a screenshot as "ss05_student4_r"
    Then I should see in course recent activity report:
      | h3 | EverybodyAccess          |               |      |
      | h3 | VisibleGroups            |               |      |
      | h3 | SeparateGroupsNoGrouping |               |      |
      | h3 | SeparateGroupsGrouping2  |               |      |
      | h3 | SeparateGroupsGrouping3  |               |      |
    And I log out
# teacher1
    And I log in as "teacher1"
    And I follow "Course1"
    And I save a screenshot as "ss06_teacher1_b"
    Then I should see in recent activity block:
      | h3    | Workshop assessments: |
      | .head | Sam2                  |
      | .info | Submission11          |
    And I open course recent activity report
    And I save a screenshot as "ss06_teacher1_r"
    Then I should see in course recent activity report:
      | h3 | EverybodyAccess          |               |      |
      |    | Submission11             | Submission by | Sam1 |
      |    | Submission21             | Submission by | Sam2 |
      |    | Submission11             | Assessment by | Sam2 |
      | h3 | VisibleGroups            |               |      |
      |    | Submission12             | Submission by | Sam1 |
      |    | Submission22             | Submission by | Sam2 |
      | h3 | SeparateGroupsNoGrouping |               |      |
      |    | Submission13             | Submission by | Sam1 |
      |    | Submission23             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping2  |               |      |
      |    | Submission14             | Submission by | Sam1 |
      |    | Submission24             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping3  |               |      |
      |    | Submission15             | Submission by | Sam1 |
      |    | Submission25             | Submission by | Sam2 |
    And I log out
# teacher2
    And I log in as "teacher2"
    And I follow "Course1"
    And I save a screenshot as "ss07_teacher2_b"
    Then I should see in recent activity block:
      | h3    | Workshop submissions: |
      | .head | Sam1                  |
      | .info | Submission11          |
      | .head | Sam1                  |
      | .info | Submission12          |
      | .head | Sam1                  |
      | .info | Submission13          |
      | .head | Sam1                  |
      | .info | Submission15          |
      | .head | Sam2                  |
      | .info | Submission21          |
      | .head | Sam2                  |
      | .info | Submission22          |
      | h3    | Workshop assessments: |
      | .head | Sam2                  |
      | .info | Submission11          |
    And I open course recent activity report
    And I save a screenshot as "ss07_teacher2_r"
    Then I should see in course recent activity report:
      | h3 | EverybodyAccess          |               |      |
      |    | Submission11             | Submission by | Sam1 |
      |    | Submission21             | Submission by | Sam2 |
      |    | Submission11             | Assessment by | Sam2 |
      | h3 | VisibleGroups            |               |      |
      |    | Submission12             | Submission by | Sam1 |
      |    | Submission22             | Submission by | Sam2 |
      | h3 | SeparateGroupsNoGrouping |               |      |
      |    | Submission13             | Submission by | Sam1 |
      | h3 | SeparateGroupsGrouping2  |               |      |
      | h3 | SeparateGroupsGrouping3  |               |      |
      |    | Submission15             | Submission by | Sam1 |
    And I log out
# teacher3
    And I log in as "teacher3"
    And I follow "Course1"
    And I save a screenshot as "ss08_teacher3_b"
    Then I should see in recent activity block:
      | h3    | Workshop submissions: |
      | .head | Sam1                  |
      | .info | Submission11          |
      | .head | Sam1                  |
      | .info | Submission12          |
      | .head | Sam2                  |
      | .info | Submission21          |
      | .head | Sam2                  |
      | .info | Submission22          |
      | .head | Sam2                  |
      | .info | Submission23          |
      | h3    | Workshop assessments: |
      | .head | Sam2                  |
      | .info | Submission11          |
    And I open course recent activity report
    And I save a screenshot as "ss08_teacher3_r"
    Then I should see in course recent activity report:
      | h3 | EverybodyAccess          |               |      |
      |    | Submission11             | Submission by | Sam1 |
      |    | Submission21             | Submission by | Sam2 |
      |    | Submission11             | Assessment by | Sam2 |
      | h3 | VisibleGroups            |               |      |
      |    | Submission12             | Submission by | Sam1 |
      |    | Submission22             | Submission by | Sam2 |
      | h3 | SeparateGroupsNoGrouping |               |      |
      |    | Submission23             | Submission by | Sam2 |
      | h3 | SeparateGroupsGrouping2  |               |      |
      | h3 | SeparateGroupsGrouping3  |               |      |
    And I log out
