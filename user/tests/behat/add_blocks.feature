@core @core_user
Feature: Add blocks to my profile page
  In order to add more functionality to my profile page
  As a user
  I need to add blocks to my profile page

  Background:
    Given I log in as "admin"
    And I follow "View profile"

  Scenario: Add blocks to page
    When I press "Customise this page"
    And I add the "Latest news" block
    Then I should see "Latest news"