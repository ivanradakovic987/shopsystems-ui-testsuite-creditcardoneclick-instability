Feature: CreditCardConfigurationHappyPath
  As admin user
  I want to check that the credit card configuration page is working properly

  Background:
    Given I initialize shop system

  @woocommerce @test
  Scenario Outline: initial transaction Non 3DS
    Given I deactivate "CreditCard" payment method in configuration
    When I go into the configuration mask as "admin user" and activate "CreditCard" method
    And I fill fields with "CreditCard" data for payment action <payment_action> and transaction type <transaction_type>
    #And I go to Payment page and check that "CreditCard" payment method is enabled
    Then I see that "CreditCard" payment method is enabled on Payment page
    And I see all data that was entered is shown in "CreditCard" configuration mask
    And I see that test credentials check provides a successful result for "CreditCard" payment method

    Examples:
      | payment_action  | transaction_type |
      |    "reserve"    | "authorization"  |
      #|      "pay"      | "purchase"       |
