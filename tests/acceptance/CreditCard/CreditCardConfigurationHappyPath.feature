Feature: CreditCardConfigurationHappyPath
  As admin user
  I want to check that the credit card configuration page is working properly

  Background:
    Given I initialize shop system

  @woocommerce
  Scenario Outline: Configuration page check
    Given I deactivate "CreditCard" payment method in configuration
    When I go into the configuration page as "admin user" and activate "CreditCard" method
    And I fill fields with "CreditCard" data for payment action <payment_action> and transaction type <transaction_type>
    Then I see that "CreditCard" payment method is enabled on Payment page
    And I see all data that was entered is shown in "CreditCard" configuration page
    And I see that test credentials check provides a successful result for "CreditCard" payment method

    Examples:
      | payment_action  | transaction_type |
      |    "reserve"    | "authorization"  |
      |      "pay"      | "purchase"       |
