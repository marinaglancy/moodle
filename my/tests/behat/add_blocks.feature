@core @core_my
Feature: Add blocks to my home page
  In order to add more functionality to my home page
  As a user
  I need to add blocks to my home page

  Background:
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
    And I log in as "student1"
    And I follow "My home"

  Scenario: Add blocks to page
    When I press "Customise this page"
    And I add the "Latest news" block
    And I add the "My latest badges" block
    Then I should see "Latest news"
    And I should see "My latest badges"