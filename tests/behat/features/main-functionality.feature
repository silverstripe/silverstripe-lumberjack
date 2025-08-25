@retry
Feature: Main functionality
  As a content author
  I want lumberjack to hide some pages from the sitetree
  So that I have good performance for more frequently used pages

  Background:
    Given I add an extension "SilverStripe\Lumberjack\Model\Lumberjack" to the "GridFieldTestPage" class
      And I have a config file "hide-upload-pages.yml"
      # Set up lumberjack pages
      And a "GridFieldTestPage" "Lumberjack parent page"
      And a "TestFileUploadPage" "Lumberjack hidden child"
      And a "GridFieldTestPage" "Lumberjack shown child 1"
      And a "page" "Lumberjack shown child 2"
      And the "TestFileUploadPage" "Lumberjack hidden child" is a child of a "GridFieldTestPage" "Lumberjack parent page"
      And the "GridFieldTestPage" "Lumberjack shown child 1" is a child of a "GridFieldTestPage" "Lumberjack parent page"
      And the "page" "Lumberjack shown child 2" is a child of a "GridFieldTestPage" "Lumberjack parent page"
      # Set up regular pages
      And a "page" "Regular parent"
      And a "TestFileUploadPage" "regular child 1"
      And a "GridFieldTestPage" "regular child 2"
      And a "page" "regular child 3"
      And the "TestFileUploadPage" "regular child 1" is a child of a "page" "Regular parent"
      And the "GridFieldTestPage" "regular child 2" is a child of a "page" "Regular parent"
      And the "page" "regular child 3" is a child of a "page" "Regular parent"
      # Set up user
      And the "group" "EDITOR" has permissions "Access to 'Pages' section"
      And I am logged in as a member of "EDITOR" group

  Scenario: Sitetree tree view - default method
    Given I go to "/admin/pages"
    Then I should see "Regular parent" in the tree
      And I should see "regular child 1" in the tree
      And I should see "regular child 2" in the tree
      And I should see "regular child 3" in the tree
      And I should see "Lumberjack parent page" in the tree
      And I should see "Lumberjack shown child 1" in the tree
      And I should see "Lumberjack shown child 2" in the tree
      And I should not see "Lumberjack hidden child" in the tree
    When I click on "Lumberjack parent page" in the tree
      And I click the "Child Pages" CMS tab
      Then I should see "Lumberjack hidden child" in the "#Form_EditForm_ChildPages" element
      And I should not see "Lumberjack shown child 1" in the "#Form_EditForm_ChildPages" element
      And I should not see "Lumberjack shown child 2" in the "#Form_EditForm_ChildPages" element

  Scenario: Sitetree tree view - legacy method
    Given I have a config file "legacy-tree-method.yml"
      And I go to "/admin/pages"
    Then I should see "Regular parent" in the tree
      And I should see "regular child 1" in the tree
      And I should see "regular child 2" in the tree
      And I should see "regular child 3" in the tree
      And I should see "Lumberjack parent page" in the tree
      And I should see "Lumberjack shown child 1" in the tree
      And I should see "Lumberjack shown child 2" in the tree
      And I should not see "Lumberjack hidden child" in the tree
    When I click on "Lumberjack parent page" in the tree
      And I click the "Child Pages" CMS tab
      Then I should see "Lumberjack hidden child" in the "#Form_EditForm_ChildPages" element
      And I should not see "Lumberjack shown child 1" in the "#Form_EditForm_ChildPages" element
      And I should not see "Lumberjack shown child 2" in the "#Form_EditForm_ChildPages" element
