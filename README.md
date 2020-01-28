# **Concept for UI test code**

To run tests locally:
========

1. Start the shop system with our extension installed
2. Start selenium driver on port 4444
3. Clone 
    ```
    git clone https://github.com/wirecard/shopsystems-ui-testsuite.git
    
    cd shopsystems-ui-testsuite
 

4. Install codeception
    
    `composer install codeception/codeception`
5. Export environment variables

    `SHOP_SYSTEM = prestashop #(or woocommerce)`
        
     `DB_HOST`
        
     `DB_PORT`
        
     `DB_NAME`
        
     `SHOP_URL`
        
     if running on browserstack
        `BROWSERSTACK_USER`
        `BROWSERSTACK_ACCESS_KEY`
6. Start codeception 
    
    `vendor/bin/codecept run acceptance  --debug --html`

Configuring test data
=====
It is possible instead of specified test data (Customer information, payment method credentials (like credit card numbers, PayPal credentials, etc)) to use custom one.
For that use `config.json` file. There it is possible to change used currency, default shop system country and path to data files.

`config.json` file content:
`````
{
     "gateway" : "API-TEST",
     "customer_data": "CustomerData.json",
     "currency": "EUR",
     "default_country": "AT",
     "creditcard_data": "CreditCardData.json",
     "paypal_data": "PayPalData.json"
}
`````
Note: if you want to use custom `*_data.json` path, please put the full file path. Otherwise the file should be located in respective folder inside `_data` folder.


Structure
=====


    .
    ├── tests                           # All files
    |    ├── _data       
    |    |  ├── Customer                # Customer (shop user) data
    |    |  |   ├──...      
    |    |  ├── Locator                 # Locators     
    |    |  |   ├──...       
    |    |  ├── PaymentMethodConfig     # Payment method configuration data (maid, user, password, etc...) 
    |    |  |   ├──... 
    |    |  ├── PaymentMethodData       # Payment method data (crecit card numbers, user, password, ...)
    |    |  |   ├──... 
    |    ├── _support                   # All helper classes 
    |    |  ├── Helper                  
    |    |  |   ├── Config              # Classes responsible for handling data
    |    |  |   |   ├── Customer        # Classes handling customer data
    |    |  |   |   |   ├──...   
    |    |  |   |   ├── PaymentMethod   # Classes handling payment method data
    |    |  |   |   |   ├──...
    |    |  |   |   ├── FileSystem.php  # Path constants
    |    |  |   |   ├──... 
    |    |  |   ├── Acceptance.php       # All helper functions that AcceptanceTester.php and it's child classes can use
    |    |  |   ├── DbHelper.php         # All Db related helper funcitons
    |    |  ├── Step                     # Here we keep all the AcceptanceTester.php child classes that group different steps by their functionality
    |    |  |   ├── Acceptance           
    |    |  |   |   ├── PaymentMethod    # Steps specific for payment method       
    |    |  |   |   |   ├── ...           
    |    |  |   |   ├── ShopSystem       # Steps specific for shop system       
    |    |  |   |   |   ├── ...         
    |    |  |   |   ├── ...              # Generic classes and interfaces
    |    |  ├── AcceptanceTester.php     # Main orchestrator class, that calls specific steps
    |    ├── acceptance  
    |    |  ├── CreditCard              # Actual test cases for credit card
    |    |  ├── PayPal
    |    |  ├── ...  
    |    ├── acceptance.yml           # Acceptance test configuration         
    ├── codeception.yml                # Codeception configuration files
    ├── config.json                    # Basic configuration of test data
    └── README.md

Flow
=====
Example for Credit Card test on Woocommerce

Entry point: `CreditCard/CreditCard3DSAuthorizationHappyPath.feature`  each line is linked to 

orchestrator: `_support/AcceptanceTester.php`  . It is responsible for initialization 
and calls steps from child instances: 

shop instance `_support/Step/Acceptane/ShopSystem/WoocommerceStep.php` for steps specific to the shop system (like filling basket, going to checkout)

or 

payment method instance `_support/Step/Acceptane/PaymentMethod/CreditCardStep.php` for steps specific to payment method (like filling credit card forms and going to ACS page)


Both shop instance and payment method instance use 

locators from `_data/Locator`

data from `_data/Customer` for customer information (name, address, phone, ...)

`_data/PaymentMethodData`  for payment method information (credit card number, cvv, ...)