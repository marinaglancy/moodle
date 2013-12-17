@core @core_user
Feature: Reset my profile page to default
  In order to remove customisations from my profile page
  As a user
  I need to reset my profile page

  Background:
    Given I log in as "admin"
    And I follow "View profile"

  Scenario: Add blocks to page and reset
    When I press "Customise this page"
    And I add the "Latest news" block
    And I press "Reset page to default"
    Then I should not see "Latest news"
    And "Customise this page" "button" should exists
    And "Reset page to default" "button" should not exists