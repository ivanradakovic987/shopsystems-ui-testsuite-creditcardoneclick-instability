# **Concept for UI test code**

To run tests locally:
========

1. Start the shop system
2. Start selenium driver on port 4444
3. Install codeception
    
    `composer install codeception/codeception`
4. Export environment variables

        `SHOP_SYSTEM = prestashop #(or woocommerce)`
        
        `DB_HOST`
        
        `DB_PORT`
        
        `DB_NAME`
        
        `SHOP_URL`
        
        if running on browserstack
        `BROWSERSTACK_USER`
        `BROWSERSTACK_ACCESS_KEY`
5. Start codeception 
    
    `vendor/bin/codecept run acceptance  --debug --html`

Structure
=====


    .
    ├── tests                           # All files
    |    ├── _data       
    |       ├── Customer                # Customer (shop user) data
    |         ├──...      
    |       ├── Locator                 # Locators     
    |         ├──...       
    |       ├── PaymentMethodConfig     # Payment method configuration data (maid, user, password, etc...) 
    |         ├──... 
    |       ├── PaymentMethodData       # Payment method data (crecit card numbers, user, password, ...)
    |         ├──... 
    |    ├── _support                   # All helper classes 
    |       ├── Helper                  
    |           ├── Config              # Classes responsible for handling data
    |               ├── Customer        # Classes handling customer data
    |                   ├──...   
    |               ├── PaymentMethod   # Classes handling customer data
    |                   ├──...
    |               ├── FileSystem.php  # Path constants
    |               ├──... 
    |           ├── Acceptance.php       # All helper functions that AcceptanceTester.php and it's child classes can use
    |           ├── DbHelper.php         # All Db related helper funcitons
    |       ├── Step                     # Here we keep all the AcceptanceTester.php child classes that group different steps by their functionality
    |           ├── Acceptance           
    |               ├── PaymentMethod    # Specific payment method steps       
    |                   ├── ...           
    |               ├── ShopSystem       # Specific shop system steps       
    |                   ├── ...         
    |               ├── ...              # Generic classes and interfaces
    |       ├── AcceptanceTester.php     # Main orchestrator class, that calls specific steps
    |    ├── acceptance  
    |       ├── CreditCard              # Actual test cases for credit card
    |       ├── PayPal
    |       ├── ...  
    |     ├── acceptance.yml           # Acceptance test configuration         
    ├── codeception.yml                # Codeception configuration files
    ├── config.json                    # Basic configuration of test data
    └── README.md

Flow
=====

`CreditCard/CreditCard3DSAuthorizationHappyPath.feature` - is entry point - each line is linked to 
`_support/AcceptanceTester.php` which is an orchestrator. It is responsible for initialization 
and calls steps from child instances (shop or payment method).



