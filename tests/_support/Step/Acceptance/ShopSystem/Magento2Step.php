<?php

namespace Step\Acceptance\ShopSystem;

use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Exception\UnknownServerException;
use NoSuchElementException as NoSuchElementExceptionAlias;
use Step\Acceptance\iConfigurePaymentMethod;
use Step\Acceptance\iPrepareCheckout;
use Step\Acceptance\iValidateSuccess;
use Exception;

/**
 * Class PrestashopStep
 * @package Step\Acceptance|ShopSystem
 */
class Magento2Step extends GenericShopSystemStep implements iConfigurePaymentMethod, iPrepareCheckout, iValidateSuccess
{
    const STEP_NAME = 'Magento2';

    const SETTINGS_TABLE_NAME = 'core_config_data';

    const NAME_COLUMN_NAME = 'path';

    const VALUE_COLUMN_NAME = 'value';

    const DB_SEPARATOR = '/';

    const PAYMENT_METHOD_PREFIX = 'payment/wirecard_elasticengine_';

    const TRANSACTION_TABLE_NAME = 'sales_payment_transaction';

    const TRANSACTION_TYPE_COLUMN_NAME = 'txn_type';

    const DEFAULT_COUNTRY_OPTION_NAME = 'general/country/default';

    const CURRENCY_OPTION_NAME = 'currency/options/base';

    const CREDIT_CARD_ONE_CLICK_CONFIGURATION_OPTION = 'cc_vault/active';

    const CUSTOMER_TABLE = 'customer_entity';

    const CUSTOMER_EMAIL_COLUMN_NAME = 'email';

    const CUSTOMER_ADDRESS_TABLE = 'customer_address_entity';

    const MAGENTO_CACHE_CLEAN_COMMAND = ' php bin/magento cache:clean';

    const MAGENTO_CACHE_FLUSH_COMMAND = ' php bin/magento cache:flush';

    const MAGENTO_CRON_RUN_COMMAND = ' /usr/local/bin/php /var/www/html/bin/magento cron:run';

    /**
     * @var array
     */
    private $configNameDiffs =
        [
            'cc_vault_enabled' => 'cc_vault/active',
            'enabled' => 'active'
        ];

    /**
     * @param String $paymentMethod
     * @param String $paymentAction
     * @return mixed|void
     * @throws Exception
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
    {
        $actingPaymentMethod = $this->getActingPaymentMethod($paymentMethod);
        $db_config = $this->buildPaymentMethodConfig($actingPaymentMethod, $paymentAction, $this->getMappedPaymentActions(), $this->getGateway());
        foreach ($db_config as $name => $value) {
            //some configuration options are different if different shops, this is handling the differences
            if (array_key_exists($name, $this->configNameDiffs)) {
                $name = $this->configNameDiffs[$name];
            }
            $fullName = self::PAYMENT_METHOD_PREFIX . strtolower($actingPaymentMethod) . static::DB_SEPARATOR . strtolower($name);
            $this->putValueInDatabase($fullName, $this->convertWordValueToBinaryString($value));
        }
        if (strcasecmp($paymentMethod, static::CREDIT_CARD_ONE_CLICK) === 0) {
            $this->putValueInDatabase(static::PAYMENT_METHOD_PREFIX . static::CREDIT_CARD_ONE_CLICK_CONFIGURATION_OPTION, '1');
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function registerCustomer(): void
    {
        if (!$this->isCustomerRegistered()) {
            $this->amOnPage($this->getLocator()->page->register);
            $this->preparedFillField($this->getLocator()->register->first_name, $this->getCustomer(static::REGISTERED_CUSTOMER)->getFirstName());
            $this->preparedFillField($this->getLocator()->register->last_name, $this->getCustomer(static::REGISTERED_CUSTOMER)->getLastName());
            $this->preparedFillField($this->getLocator()->register->email_address, $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress());
            $this->preparedFillField($this->getLocator()->register->password, $this->getCustomer(static::REGISTERED_CUSTOMER)->getPassword());
            $this->preparedFillField($this->getLocator()->register->confirm_password, $this->getCustomer(static::REGISTERED_CUSTOMER)->getPassword());
            $this->preparedClick($this->getLocator()->register->create_an_account);
            $this->amOnPage($this->getLocator()->page->log_out);
     }
        $this->configureRegisteredCustomerAddressInDataBase();
    }

    /**
     * @param String $paymentMethod
     * @return mixed
     * @throws Exception
     */
    public function startPayment($paymentMethod): void
    {
        if (strpos($paymentMethod, 'OneClick') === false ) {
            $paymentMethodName = strtolower($paymentMethod) . '_name';
            $paymentMethodForm = strtolower($paymentMethod) . '_form';
            $this->waitUntil(80, [$this, 'waitUntilOptionSelected'],
                [$this->getLocator()->payment->$paymentMethodForm, $this->getLocator()->payment->$paymentMethodName]);
            if ($this->isRedirectPaymentMethod($paymentMethod)) {
                $this->preparedClick($this->getLocator()->payment->place_order);
            }
        }
    }

    /**
     * @param String $paymentMethod
     * @return mixed
     * @throws Exception
     */
    public function proceedWithPayment($paymentMethod): void
    {
        $this->preparedClick($this->getProceedWithPaymentLocator($paymentMethod));
    }

    /**
     * @param String $minPurchaseSum
     * @throws Exception
     */
    public function fillBasket($minPurchaseSum): void
    {
        parent::fillBasket($minPurchaseSum);
        $this->waitForText('You added', 60);
    }

    /**
     * @param string $customerType
     * @throws Exception
     */
    public function fillCustomerDetails($customerType): void
    {
        $this->waitUntil(60, [$this, 'waitUntilPageLoaded'], [$this->getLocator()->page->checkout]);
        if ($customerType !== static::REGISTERED_CUSTOMER) {
            $this->preparedFillField($this->getLocator()->checkout->email_address, $this->getCustomer($customerType)->getEmailAddress(),80);
            $this->preparedFillField($this->getLocator()->checkout->first_name, $this->getCustomer($customerType)->getFirstName());
            $this->preparedFillField($this->getLocator()->checkout->last_name, $this->getCustomer($customerType)->getLastName());
            $this->fillBillingDetails($customerType);
            $this->selectOption($this->getLocator()->checkout->country, $this->getCustomer($customerType)->getCountry());
            $this->preparedSelectOption($this->getLocator()->checkout->state, $this->getCustomer($customerType)->getState());
        }
        //this magento view is very flaky, after the address is filled the shop is loading the delivery options
        // and the button is active or not active at random times, we have to wait to safely click the button
        $this->wait(5);
        try {
            $this->preparedClick($this->getLocator()->checkout->next, 80);
        }
        catch (UnknownServerException $e)
        {
            $this->wait(10);
            $this->preparedClick($this->getLocator()->checkout->next, 80);
        }
        $this->waitUntil(60, [$this, 'waitUntilPageLoaded'], [$this->getLocator()->page->payment]);
        $this->wait(3);
    }

    /**
     * @throws Exception
     */
    public function logIn()
    {
        $this->amOnPage($this->getLocator()->page->sign_in);
        if (!$this->isCustomerSignedIn()) {
            $this->preparedFillField($this->getLocator()->sign_in->email, $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress());
            $this->preparedFillField($this->getLocator()->sign_in->password, $this->getCustomer(static::REGISTERED_CUSTOMER)->getPassword());
            $this->preparedClick($this->getLocator()->sign_in->sign_in, 60);
        }
    }

    /**
     * @return bool
     */
    public function isCustomerRegistered(): bool
    {
        $guest = $this->grabFromDatabase(static::CUSTOMER_TABLE, static::CUSTOMER_EMAIL_COLUMN_NAME,
            [static::CUSTOMER_EMAIL_COLUMN_NAME => $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress()]);
        return $guest !== false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function isCustomerSignedIn(): bool
    {
        try {
            $this->waitForText($this->getLocator()->account->my_account,2);
            return true;
        }
        catch (TimeOutException $e)
        {
           return false;
        }
    }

    /**
     *
     */
    private function configureRegisteredCustomerAddressInDataBase()
    {
        $entityId = $this->grabFromDatabase(static::CUSTOMER_TABLE, 'entity_id',
            [static::CUSTOMER_EMAIL_COLUMN_NAME => $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress()]);
        $this->updateInDatabase(static::CUSTOMER_TABLE,
            ['default_shipping' => $entityId, 'default_billing' => $entityId],
            [static::CUSTOMER_EMAIL_COLUMN_NAME => $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress()]);
        $this->haveInDatabase(static::CUSTOMER_ADDRESS_TABLE,
            ['entity_id' => $entityId,
            'parent_id' => $entityId,
            'created_at' => date('Y-m-d h:i:s'),
            'updated_at' => date('Y-m-d h:i:s'),
            'is_active' => '1',
            'city' => $this->getCustomer(static::REGISTERED_CUSTOMER)->getTown(),
            'country_id' => $this->getCustomer(static::REGISTERED_CUSTOMER)->getCountryId(),
            'firstname' => $this->getCustomer(static::REGISTERED_CUSTOMER)->getFirstName(),
            'lastname' => $this->getCustomer(static::REGISTERED_CUSTOMER)->getLastName(),
            'postcode' => $this->getCustomer(static::REGISTERED_CUSTOMER)->getPostCode(),
            'region_id' => '0',
            'street' => $this->getCustomer(static::REGISTERED_CUSTOMER)->getStreetAddress(),
            'telephone' => $this->getCustomer(static::REGISTERED_CUSTOMER)->getPhone()]);
    }

    /**
     * @param $paymentMethod
     * @return mixed
     */
    private function getProceedWithPaymentLocator($paymentMethod)
    {
        if ($paymentMethod === 'CreditCard')
        {
            return $this->getLocator()->payment->credit_card_place_order;
        }
        return $this->getLocator()->payment->place_order;
    }
}
