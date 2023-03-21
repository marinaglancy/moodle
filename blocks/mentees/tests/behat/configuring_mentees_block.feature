@block @block_mentees @core_block
Feature: Adding and configuring Mentees blocks
  In order to have a Mentees blocks on a page
  As admin
  I need to be able to insert and configure a Mentees blocks

  @javascript
  Scenario: Configuring the Mentees block with Javascript on
    Given I log in as "admin"
    And I am on site homepage
    When I turn editing mode on
    And I add the "Mentees" block to the default region with:
      | Mentees block title (no title if blank) | The Mentees block header |
    And "block_mentees" "block" should exist
    Then "The Mentees block header" "block" should exist
