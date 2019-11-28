Feature: CreditCard3DSAuthorizationHappyPath
  As a guest  user
  I want to make an authorization with a Credit Card 3DS
  And to see that authorization was successful

  Background:
    Given I initialize shopsystem
#    configure payment method in db
    And I activate "CreditCard" payment action "reserve" in configuration
# do all actions in shopsystem that lead to choosing payment method
    And I prepare checkout with purchase sum "100" in shopsystem
    Then I see "Wirecard Credit Card"
# in some shop systems there is a need to press the button
    And I start "CreditCard" payment

  @patch @minor @major
  Scenario: authorize
#TODO think of a better name
#TODO think about error card cases
    Given I perform "CreditCard" payment in the shop
    And I go through external flow
    Then I see successful payment
    And I see "CreditCard" transaction type "authorization" in transaction table
