@core @core_message @core_user
Feature: Manage contacts
  In order to test MDL-45818
  As an admin
  I need to be able to delete users without exceptions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | user1 | User | One | one@asd.com |
      | user2 | User | Two | two@asd.com |
    And I log in as "user1"
    And I send "Message 1 from user1 to user2" message to "User Two" user
    And I log out
    And I log in as "admin"
    And I navigate to "Bulk user actions" node in "Site administration > Users > Accounts"

  @javascript
  Scenario: Deleting sender first and the recipient second
    And I set the field "Available" to "User One"
    And I press "Add to selection"
    And I set the field "id_action" to "Delete"
    And I press "Go"
    And I press "Yes"
    And I should see "Changes saved"
    And I navigate to "Bulk user actions" node in "Site administration > Users > Accounts"
    And I set the field "Available" to "User Two"
    And I press "Add to selection"
    And I set the field "id_action" to "Delete"
    And I press "Go"
    And I press "Yes"
    And I should see "Changes saved"

  @javascript
  Scenario: Deleting recipient first and the sender second
    And I set the field "Available" to "User Two"
    And I press "Add to selection"
    And I set the field "id_action" to "Delete"
    And I press "Go"
    And I press "Yes"
    And I should see "Changes saved"
    And I navigate to "Bulk user actions" node in "Site administration > Users > Accounts"
    And I set the field "Available" to "User One"
    And I press "Add to selection"
    And I set the field "id_action" to "Delete"
    And I press "Go"
    And I press "Yes"
    And I should see "Changes saved"
