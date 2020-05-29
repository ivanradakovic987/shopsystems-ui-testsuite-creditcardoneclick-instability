Feature: GuaranteedInvoiceInitialTransactionHappyPath
  As a guest user
  I want to make an initial transaction with GuaranteedInvoice
  And to see that initial transaction was successful

  Background:
    Given I initialize shop system
#    make sure that customer has billing and shipping address defined and they are the same

  @woocommerce @test
  Scenario Outline: initial transaction
    And I activate "Invoice" payment action <payment_action> in configuration
#    make sure you are NOT buying virtual product
#   Make sure you are using AT,DE or CH address for the checkout
    And I prepare checkout with purchase sum "100" in shop system as "registered customer"
    And I see "Wirecard Guaranteed Invoice by Wirecard"
    And I start "GuaranteedInvoice" payment
    When I fill "GuaranteedInvoice" fields in the shop
    Then I see successful payment
    And I see "GuaranteedInvoice" transaction type <transaction_type> in transaction table

    Examples:
      | payment_action                                     | transaction_type |
      | "reserve"                                          | "authorization" |
