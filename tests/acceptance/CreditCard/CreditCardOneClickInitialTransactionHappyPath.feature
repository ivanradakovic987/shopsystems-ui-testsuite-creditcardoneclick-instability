Feature: CreditCardOneClickInitialTransactionHappyPath
  As a registered user
  I want to make an initial transaction with Credit Card One-Click
  And to see that initial transaction was successful

  Background:
    Given I initialize shop system

  @woocommerce @major @minor @patch
  Scenario Outline: initial transaction
    And I activate "CreditCardOneClick" payment action <payment_action> in configuration
    And I prepare checkout with purchase sum <amount> in shop system as "registered customer"
    And I see "Wirecard Credit Card"
    And I start "CreditCard" payment
    And I fill "CreditCardOneClick" fields in the shop
    And I save "CreditCardOneClick" for later use
    When I perform "CreditCard" actions outside of the shop
    And I see successful payment
    And I prepare checkout with purchase sum <amount> in shop system as "registered customer"
    And I see "Wirecard Credit Card"
    And I start "CreditCard" payment
    And I choose "CreditCardOneClick" from saved cards list
    And I perform "CreditCard" actions outside of the shop
    Then I see successful payment
    And I see "CreditCard" transaction type <transaction_type> in transaction table

    Examples:
      | payment_action  | amount | transaction_type |
      |    "reserve"    | "100"  |  "authorization" |
