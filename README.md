# **Concept for UI test code**

#### **To run tests locally:**

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
