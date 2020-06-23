Feature: EpsInitialTransactionHappyPath
  As a guest user
  I want to make an initial transaction with eps-Überweisung
  And to see that initial transaction was successful

  Background:
    Given I initialize shop system

  @woocommerce
  Scenario Outline: initial transaction
    Given I activate "eps-Überweisung" payment action <payment_action> in configuration
    And I prepare checkout with purchase sum <amount> in shop system as "guest customer"
    And I see "Wirecard eps-Überweisung"
    And I start "eps-Überweisung" payment
    And I fill "eps-Überweisung" fields in the shop
    And I place the order and continue "eps-Überweisung" payment
    And I perform "eps-Überweisung" actions outside of the shop
    Then I see successful payment
    And I see "eps-Überweisung" transaction type <transaction_type> in transaction table

    Examples:
      | payment_action  | amount | transaction_type |
      | "debit"         | 20     | "debit"          |
