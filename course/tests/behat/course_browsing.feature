@core @core_course
Feature: Restricting access to course lists
  In order to provide more targeted content
  As a Moodle Administrator
  I need to be able to give/revoke capabilities to view list of courses

  Background:
    Given the following "categories" exist:
      | name | category | idnumber |
      | Science | 0 | SCI |
      | English | 0 | ENG |
      | Other   | 0 | MISC |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Biology Y1 | BIO1 | SCI |
      | ICT Y2 | ICT01 | SCI |
      | English Y1 | ENG1 | ENG |
      | English Y2 | ENG2 | ENG |
      | Humanities Y1 | HUM2 | MISC |
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | user0 | User | Z | user0@example.com |
      | userb | User | B | userb@example.com |
      | usere | User | E | usere@example.com |
    Given the following "roles" exist:
      | name            | shortname    | description      | archetype      |
      | Category viewer | coursebrowse | My custom role 1 |                |
    Given I log in as "admin"
    And I set the following system permissions of "Authenticated user" role:
      | capability | permission |
      | moodle/course:browse | Prevent |
    And I set the following system permissions of "Guest" role:
      | capability | permission |
      | moodle/course:browse | Prevent |
    And I set the following system permissions of "Category viewer" role:
      | capability | permission |
      | moodle/course:browse | Allow |
    And I log out
    And the following "role assigns" exist:
      | user  | role           | contextlevel | reference |
      | usere | coursebrowse   | Category     | ENG       |
      | userb | coursebrowse   | Category     | ENG       |
      | userb | coursebrowse   | Category     | SCI       |

    @xxx
  Scenario: Browse courses as a user without any browse capability
    Given I log in as "user0"
