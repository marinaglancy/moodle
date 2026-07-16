@core @core_completion @mod_subsection
Feature: Subsection activities appear in course-page order in completion contexts.
  In order to ensure subsection activities are correctly ordered
  As a teacher
  I need to see that activities inside a subsection are interleaved with
  main section activities in the correct course-page order on the bulk edit completion page.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | numsections | initsections | enablecompletion |
      | Course 1 | C1        | 0        | 1           | 1            | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name         | course | section |
      | page       | Activity A   | C1     | 1       |
      | subsection | Subsection 1 | C1     | 1       |
      | page       | Activity D   | C1     | 1       |
      | page       | Activity B   | C1     | 2       |
      | page       | Activity C   | C1     | 2       |

  @javascript
  Scenario: Bulk edit completion shows subsection activities in course-page order
    Given I am on the "Course 1" course homepage with editing mode on
    When I navigate to "Course completion" in current page administration
    And I set the field "Course completion tertiary navigation" to "Bulk edit activity completion"
    Then I should see "Bulk edit activity completion"
    And I should see "Activity A" in the "region-main" "region"
    And I should see "Activity B" in the "region-main" "region"
    And I should see "Activity C" in the "region-main" "region"
    And I should see "Activity D" in the "region-main" "region"
    And "Activity A" "text" should appear before "Activity B" "text"
    And "Activity B" "text" should appear before "Activity C" "text"
    And "Activity C" "text" should appear before "Activity D" "text"
