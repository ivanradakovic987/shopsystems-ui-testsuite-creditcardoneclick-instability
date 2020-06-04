Feature: AlipayCrossBorderInitialTransaction
  As a guest user
  I want to make an initial transaction with AlipayCrossBorder
  And to see that initial transaction was successful

  Background:
    Given I initialize shop system

  @woocommerce
  Scenario Outline: initial transaction
    And I activate "Alipay-Xborder" payment action <payment_action> in configuration
    And I prepare checkout with purchase sum "100" in shop system as "guest customer"
    And I see "Wirecard Alipay Cross-Border"
    And I start "AlipayCrossBorder" payment
    #Here we can only check if we got redirected to Alipay. We cannot do full payment because of capcha
    Then I perform "AlipayCrossBorder" actions outside of the shop

    Examples:
      | payment_action |
      | "debit"        |
