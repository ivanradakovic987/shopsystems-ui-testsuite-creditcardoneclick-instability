Feature: CreditCard3DSAuthorizationHappyPath
  As a guest  user
  I want to make an authorization with a Credit Card 3DS
  And to see that authorization was successful

  Background:
    Given I initialize shop system
    And I activate "CreditCard" payment action "reserve" in configuration
    And I prepare checkout with purchase sum "100" in shop system
    And I see "Wirecard Credit Card"
    And I start "CreditCard" payment

  @patch @minor @major
  Scenario: authorize
    When I fill "CreditCard" fields in the shop
    And I perform "CreditCard" actions outside of the shop
    Then I see successful payment
    And I see "CreditCard" transaction type "authorization" in transaction table
