Feature: PayPalPurchaseHappyPath
  As a guest user
  I want to make a purchase with a Pay Pal
  And to see that transaction was successful

  Background:
    Given I initialize shopsystem
    And I activate "PayPal" payment action "pay" in configuration
    And I prepare checkout with purchase sum "100" in shopsystem
    Then I see "Wirecard PayPal"
    And I start "PayPal" payment

  @patch @minor @major
  Scenario: purchase
    Given I perform "PayPal" payment actions in the shop
    And I go through external flow
    Then I see successful payment
    And I see "PayPal" transaction type "purchase" in transaction table


