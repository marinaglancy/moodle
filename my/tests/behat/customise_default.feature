@core @core_my
Feature: Customise default home page
  In order to add functionality to all home pages
  As an admin
  I need to add blocks to the default home page

  Background:
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
    And I log in as "admin"
    And I expand "Site administration" node
    And I follow "Default My home page"
    And I press "Blocks editing on"
    And I add the "Latest news" block
    And I add the "My latest badges" block
    And I log out

  Scenario: See default blocks
    When I log in as "student1"
    And I follow "My home"
    Then I should see "Latest news"
    And I should see "My latest badges"