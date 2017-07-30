@core_enrol @core_group
Feature: Users can be added to multiple groups at once
  In order to manage group membership effectively
  As a user
  I need to add another user to multiple groups

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
      | Group 3 | C1 | G3 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | editingteacher |

  @javascript
  Scenario: Adding a user to one group
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Participants"
    And I click on "Edit groups for \"Student 1\"" "link" in the "student1" "table_row"
    And I open the autocomplete suggestions list
    And I click on "Group 1" item in the autocomplete list
    And I open the autocomplete suggestions list
    And I click on "Group 2" item in the autocomplete list
    And I press key "27" in the field "Edit groups for \"Student 1\""
    And I click on "Save changes" "link" in the "student1" "table_row"
    Then I should see "Group 1, Group 2"

  @javascript
  Scenario: Adding a user to multiple group
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Add user into group" "link" in the "student1" "table_row"
    When I set the field "Add user into group" to "Group 1, Group 2, Group 3"
    And I press "Save changes"
    Then I should see "Group 1"
    And I should see "Group 2"
    And I should see "Group 3"
