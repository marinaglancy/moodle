@mod @mod_quiz
Feature: Add and configure small quiz and perform an attempt as a student
  In order to evaluate students
  As a teacher
  I need to create a quiz

  @javascript
  Scenario: Add a quiz
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Terry1 | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1 | Student1 | student1@moodle.com |
    And the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name | Test quiz name |
    And I wait "6" seconds
    When I follow "Test quiz name"
    And I press "Edit quiz"
    And I press "Add a question"
    And I select "True/False" radio button
    And I press "Next"
    And I fill the moodle form with:
      | Question name | First question |
      | Question text | Answer the first question |
      | General feedback | Thank you, this is the general feedback |
      | Correct answer | False
      | Feedback for the response 'True'. | So you think it is true |
      | Feedback for the response 'False'. | So you think it is false |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test quiz name"
    And I press "Attempt quiz now"
    Then I should see "Question 1"
    And I should see "Answer the first question"
    When I select "True" radio button
    And I press "Next"
    Then I should see "Answer saved"
    And I press "Submit all and finish"
    And I press "Submit all and finish"
    Then I should see "So you think it is true"
    Then I should see "Thank you, this is the general feedback"
    Then I should see "The correct answer is 'False'."
    And I follow "Finish review"
    Then I should see "Highest grade: 0.00 / 10.00."
    
