@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Lexeme Page Header

Background:
  Given I am on a Lexeme page
    And The copyright warning has been dismissed
    And Anonymous edit warnings are disabled

  @integration
  Scenario: Update lexical category
    Given I have an item to test
    When I click the lexeme header edit button
     And I enter the test item id into the lexical category field
     And I click the lexeme header save button
     And I reload the lexeme page
    Then I should see the item in the lexical category field
