@mod @mod_glossary @block_recent_activity
Feature: New glossary entries are shown in recent activity block and report
  In order to view recently added glossary entries
  As a teacher
  I need to see glossary entries in recent activity block and report

  @javascript
  Scenario: View glossary entries in recent activity block and report
    Given I log in as "admin"
    And I expand "Users" node
    And I set the following system permissions of "Non-editing teacher" role:
      | moodle/site:accessallgroups | Prevent |
    And I am on homepage
    And I set the following administration settings values:
      | Enable group members only | 1 |
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
      | activity   | name           | intro                     | course | idnumber   | defaultapproval |
      | glossary   | AutoApproval | Test glossary description | c1     | glossary1  | 1               |
      | glossary   | ManualApproval | Test glossary description | c1     | glossary1  | 0               |
    And the following "activities" exist:
      | activity | name              | intro                     | course | idnumber  | groupmembersonly | grouping |
      | glossary | GlossaryGrouping1 | Test glossary description | c1     | glossary4 | 1                | GG1      |
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
    And I log in as "teacher1"
    And I follow "Course1"
    And I log out
    And I log in as "teacher2"
    And I follow "Course1"
    And I log out
    And I log in as "teacher3"
    And I follow "Course1"
    And I log out
# Student1
    When I log in as "student1"
    And I follow "Course1"
    And I should see nothing in recent activity block
    And I follow "AutoApproval"
    And I add a glossary entry with the following data:
      | Concept    | Concept11                |
      | Definition | Test concept description |
    And I follow "Course1"
    And I follow "ManualApproval"
    And I add a glossary entry with the following data:
      | Concept    | Concept12                |
      | Definition | Test concept description |
    And I follow "Course1"
    And I follow "GlossaryGrouping1"
    And I add a glossary entry with the following data:
      | Concept    | Concept13                |
      | Definition | Test concept description |
    And I follow "Course1"
    Then I should see in recent activity block:
      | h3    | New glossary entries |
      | .head | Sam1                 |
      | .info | Concept11            |
      | .head | Sam1                 |
      | .info | Concept13            |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | AutoApproval      |      |
      |    | Concept11         | Sam1 |
      | h3 | ManualApproval    |      |
      | h3 | GlossaryGrouping1 |      |
      |    | Concept13         | Sam1 |
    And I log out
# Student2
    And I log in as "student2"
    And I follow "Course1"
    And I follow "AutoApproval"
    And I add a glossary entry with the following data:
      | Concept    | Concept21                |
      | Definition | Test concept description |
    And I follow "Course1"
    And I follow "ManualApproval"
    And I add a glossary entry with the following data:
      | Concept    | Concept22                |
      | Definition | Test concept description |
    And I follow "Course1"
    And I follow "GlossaryGrouping1"
    And I add a glossary entry with the following data:
      | Concept    | Concept23                |
      | Definition | Test concept description |
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | New glossary entries |
      | .head | Sam1                 |
      | .info | Concept11            |
      | .head | Sam1                 |
      | .info | Concept13            |
      | .head | Sam2                 |
      | .info | Concept21            |
      | .head | Sam2                 |
      | .info | Concept23            |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | AutoApproval      |      |
      |    | Concept11         | Sam1 |
      |    | Concept21         | Sam2 |
      | h3 | ManualApproval    |      |
      | h3 | GlossaryGrouping1 |      |
      |    | Concept13         | Sam1 |
      |    | Concept23         | Sam2 |
    And I log out
# Student3
    And I log in as "student3"
    And I follow "Course1"
    And I follow "AutoApproval"
    And I add a glossary entry with the following data:
      | Concept    | Concept31                |
      | Definition | Test concept description |
    And I follow "Course1"
    And I follow "ManualApproval"
    And I add a glossary entry with the following data:
      | Concept    | Concept32                |
      | Definition | Test concept description |
    And I follow "Course1"
    And I should not see "GlossaryGrouping1"
    And I should see in recent activity block:
      | h3    | New glossary entries |
      | .head | Sam1                 |
      | .info | Concept11            |
      | .head | Sam2                 |
      | .info | Concept21            |
      | .head | Sam3                 |
      | .info | Concept31            |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | AutoApproval      |      |
      |    | Concept11         | Sam1 |
      |    | Concept21         | Sam2 |
      |    | Concept31         | Sam3 |
      | h3 | ManualApproval    |      |
    And I log out
# Teacher1
    And I log in as "teacher1"
    And I follow "Course1"
    And I should see in recent activity block:
      | h3    | New glossary entries |
      | .head | Sam1                 |
      | .info | Concept11            |
      | .head | Sam1                 |
      | .info | Concept12            |
      | .head | Sam1                 |
      | .info | Concept13            |
      | .head | Sam2                 |
      | .info | Concept21            |
      | .head | Sam2                 |
      | .info | Concept22            |
      | .head | Sam2                 |
      | .info | Concept23            |
      | .head | Sam3                 |
      | .info | Concept31            |
      | .head | Sam3                 |
      | .info | Concept32            |
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | AutoApproval      |      |
      |    | Concept11         | Sam1 |
      |    | Concept21         | Sam2 |
      |    | Concept31         | Sam3 |
      | h3 | ManualApproval    |      |
      |    | Concept12         | Sam1 |
      |    | Concept22         | Sam2 |
      |    | Concept32         | Sam3 |
      | h3 | GlossaryGrouping1 |      |
      |    | Concept13         | Sam1 |
      |    | Concept23         | Sam2 |
    # Approve the entry (wait a little so we don't have timing problems in viewing recent acitivity block)
    And I wait "10" seconds
    And I follow "Course1"
    And I follow "ManualApproval"
    And I follow "Waiting approval"
    And I click on "//td[contains(.,'Concept12')]/descendant-or-self::a[./img[@alt='Approve']]" "xpath_element"
    And I wait "1" seconds
    And I click on "//td[contains(.,'Concept22')]/descendant-or-self::a[./img[@alt='Approve']]" "xpath_element"
    And I wait "1" seconds
    And I click on "//td[contains(.,'Concept32')]/descendant-or-self::a[./img[@alt='Approve']]" "xpath_element"
    And I log out
# Student1
    And I log in as "student1"
    And I follow "Course1"
    # Exact content of recent activity block on the consecutive login can not be guaranteed unless there were at least 100s between logins.
    And "Concept12" "link" should exist in the "Recent activity" "block"
    And "Concept22" "link" should exist in the "Recent activity" "block"
    And "Concept32" "link" should exist in the "Recent activity" "block"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | AutoApproval      |      |
      |    | Concept11         | Sam1 |
      |    | Concept21         | Sam2 |
      |    | Concept31         | Sam3 |
      | h3 | ManualApproval    |      |
      |    | Concept12         | Sam1 |
      |    | Concept22         | Sam2 |
      |    | Concept32         | Sam3 |
      | h3 | GlossaryGrouping1 |      |
      |    | Concept13         | Sam1 |
      |    | Concept23         | Sam2 |
    And I log out
# Student2
    And I log in as "student2"
    And I follow "Course1"
    And "Concept12" "link" should exist in the "Recent activity" "block"
    And "Concept22" "link" should exist in the "Recent activity" "block"
    And "Concept32" "link" should exist in the "Recent activity" "block"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | AutoApproval      |      |
      |    | Concept11         | Sam1 |
      |    | Concept21         | Sam2 |
      |    | Concept31         | Sam3 |
      | h3 | ManualApproval    |      |
      |    | Concept12         | Sam1 |
      |    | Concept22         | Sam2 |
      |    | Concept32         | Sam3 |
      | h3 | GlossaryGrouping1 |      |
      |    | Concept13         | Sam1 |
      |    | Concept23         | Sam2 |
    And I log out
# Student3
    And I log in as "student3"
    And I follow "Course1"
    And "Concept12" "link" should exist in the "Recent activity" "block"
    And "Concept22" "link" should exist in the "Recent activity" "block"
    And "Concept32" "link" should exist in the "Recent activity" "block"
    And I open course recent activity report
    And I should see in course recent activity report:
      | h3 | AutoApproval      |      |
      |    | Concept11         | Sam1 |
      |    | Concept21         | Sam2 |
      |    | Concept31         | Sam3 |
      | h3 | ManualApproval    |      |
      |    | Concept12         | Sam1 |
      |    | Concept22         | Sam2 |
      |    | Concept32         | Sam3 |
    And I log out
