@block @block_nurs_navigation
Feature: Customize Section Icons
  In order to customize section icons
  As an administrator
  I need to upload a custom icon and select the type

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | cjamieso@ualberta.ca |
      | student1 | Test | Student | cjamieso@gmx.ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | admin    | C1 | editingteacher |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Nursing Navigation" block
    And I configure the "Course Sections" block
    And I set the following fields to these values:
      | Show sections | 1 |
      | Disable exams link | 1 |
      | Disable assignments link | 1 |
      | Disable quests link | 1 |
    And I press "Save changes"

  @javascript @_file_upload
  Scenario: Customize icons as an administrator
    When I click on "Edit image settings" "link" in the "Course Sections" "block"
    And I upload "blocks/nurs_navigation/pix/quest.png" file to "Choose a new image:" filemanager
    And I press "Save changes"
    Then "//img[contains(@src, 'quest.png')]" "xpath_element" should exist
    When I click on "Edit image settings" "link" in the "Course Sections" "block"
    And I set the field "Update in this course only" to "1"
    And I upload "blocks/nurs_navigation/pix/assign.png" file to "Choose a new image:" filemanager
    And I press "Save changes"
    Then "//img[contains(@src, 'assign.png')]" "xpath_element" should exist
    And "//img[contains(@src, 'quest.png')]" "xpath_element" should not exist
    When I click on "Edit image settings" "link" in the "Course Sections" "block"
    And I set the field "Delete icon" to "1"
    And I press "Save changes"
    Then "//img[contains(@src, 'assign.png')]" "xpath_element" should not exist
    Then "//img[contains(@src, 'quest.png')]" "xpath_element" should exist
