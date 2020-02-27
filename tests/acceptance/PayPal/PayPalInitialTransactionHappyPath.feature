Feature: PayPal
  As a guest  user
  I want to make a transaction with a PayPal
  And to see that transaction was successful

  Background:
    Given I initialize shop system

  @woocommerce @prestashop
  Scenario Outline: initial transaction
    And I activate "PayPal" payment action <payment_action> in configuration
    And I prepare checkout with purchase sum "100" in shop system as "guest customer"
    And I see "Wirecard PayPal"
    And I start "PayPal" payment
    When I perform "PayPal" actions outside of the shop
    Then I see successful payment
    And I see "PayPal" transaction type <transaction_type> in transaction table

    Examples:
      | payment_action                                     | transaction_type |
      | "pay"                                              | "purchase" |
      | "reserve"                                          | "authorization" |
