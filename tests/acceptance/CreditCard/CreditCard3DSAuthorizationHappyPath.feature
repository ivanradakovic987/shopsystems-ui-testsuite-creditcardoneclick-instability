Feature: CreditCard3DSAuthorizationHappyPath
  As a guest  user
  I want to make an authorization with a Credit Card 3DS
  And to see that authorization was successful

  Background:
    Given I initialize shopsystem
    And I activate "CreditCard" payment action "reserve" in configuration
    And I prepare checkout with purchase sum "100" in shopsystem
    Then I see "Wirecard Credit Card"
    And I start "CreditCard" payment

  @patch @minor @major
  Scenario: authorize
    Given I perform "CreditCard" payment actions in the shop
    And I go through external flow
    Then I see successful payment
    And I see "CreditCard" transaction type "authorization" in transaction table
