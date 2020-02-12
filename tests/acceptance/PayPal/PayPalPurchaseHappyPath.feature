Feature: PayPalPurchaseHappyPath
  As a guest user
  I want to make a purchase with a Pay Pal
  And to see that transaction was successful

  Background:
    Given I initialize shop system
    And I activate "PayPal" payment action "pay" in configuration
    And I prepare checkout with purchase sum "100" in shop system as "guest customer"
    And I see "Wirecard PayPal"
    And I start "PayPal" payment

  @woocommerce @prestashop
  Scenario: purchase
    When I perform "PayPal" actions outside of the shop
    Then I see successful payment
    And I see "PayPal" transaction type "purchase" in transaction table
