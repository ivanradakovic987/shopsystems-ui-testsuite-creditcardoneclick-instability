Feature: CreditCard3DSOneClickHappyPath
  As a registered user
  I want to make a one-click checkout with a Credit Card 3DS
  And to see that transaction was successful

  Background:
    Given I initialize shop system
    And I activate "CreditCardOneClick" payment action "reserve" in configuration
    And I prepare checkout with purchase sum "100" in shop system as "registered customer"
    And I see "Wirecard Credit Card"
    And I start "CreditCard" payment

  @prestashop
  Scenario: authorize
    When I fill "CreditCardOneClick" fields in the shop
    And I save "CreditCardOneClick" for later use
    And I perform "CreditCard" actions outside of the shop
    And I see successful payment
    And I prepare checkout with purchase sum "100" in shop system as "registered customer"
    And I see "Wirecard Credit Card"
    And I start "CreditCard" payment
    And I choose "CreditCardOneClick" from saved cards list
    And I perform "CreditCard" actions outside of the shop
    Then I see successful payment
    And I see "CreditCard" transaction type "authorization" in transaction table