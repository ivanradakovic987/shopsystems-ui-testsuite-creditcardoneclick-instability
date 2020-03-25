<?php

namespace Step\Acceptance\ShopSystem;

use Step\Acceptance\iConfigurePaymentMethod;
use Step\Acceptance\iPrepareCheckout;
use Step\Acceptance\iValidateSuccess;

use Facebook\WebDriver\Exception\NoSuchElementException;

use Exception as ExceptionAlias;

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

    /**
     * @var array
     */
    private $mappedPaymentActions = [
        'CreditCard' => [
            'config' => [
                'row' => 'payment_action',
                'reserve' => 'authorize',
                'pay' => 'authorize_capture'
            ],
            'tx_table' => [
                'authorization' => 'authorization',
                'purchase' => 'capture'
            ]
        ],
        'PayPal' => [
            'config' => [
                'row' => 'payment_action',
                'reserve' => 'authorize',
                'pay' => 'authorize_capture'
            ],
            'tx_table' => [
                'authorization' => 'authorization',
                'purchase' => 'debit'
            ]
        ]
    ];
    /**
     * @var array
     */
    private $paymentMethodConfigurationNameExceptions =
        [
            'cc_vault_enabled' => 'cc_vault/active',
            'enabled' => 'active'
        ];

    /**
     * @param String $paymentMethod
     * @param String $paymentAction
     * @return mixed|void
     * @throws ExceptionAlias
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
    {
        $actingPaymentMethod = $this->getActingPaymentMethod($paymentMethod);
        $db_config = $this->buildPaymentMethodConfig($actingPaymentMethod, $paymentAction, $this->mappedPaymentActions, $this->getGateway());
        if (strcasecmp($paymentMethod, static::CREDIT_CARD_ONE_CLICK) === 0) {
            //CreditCard One click is not a separate payment method but a configuration of CreditCard
            $db_config[self::CREDIT_CARD_ONE_CLICK_CONFIGURATION_OPTION] = '1';
        }
        foreach ($db_config as $name => $value) {
            //some configuration options are different if different shops, this is handling the differences
            if (array_key_exists($name, $this->paymentMethodConfigurationNameExceptions)) {
                $name = $this->paymentMethodConfigurationNameExceptions[$name];
            }
            $fullName = self::PAYMENT_METHOD_PREFIX . strtolower($actingPaymentMethod) . static::DB_SEPARATOR . strtolower($name);
            $this->putValueInDatabase($fullName, $this->convertWordValueToBinaryString($value));

            if (strpos($fullName, "payment_action") !== false)
            {     //clean magento2 cache to for changes in database to come in place
                exec("docker exec -it " . getenv("MAGENTO_CONTAINER_NAME") . " php bin/magento cache:clean");
                exec("docker exec -it " . getenv("MAGENTO_CONTAINER_NAME") . " php bin/magento cache:flush");
            }
        }
    }

    /**
     * @return mixed
     * @throws ExceptionAlias
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
            $this->pause();
            $this->amOnPage($this->getLocator()->page->log_out);
        }
    }

    /**
     * @param String $paymentMethod
     * @return mixed
     * @throws ExceptionAlias
     */
    public function startPayment($paymentMethod): void
    {
        $paymentMethodName = strtolower($paymentMethod) . '_name';
        $paymentMethodForm = strtolower($paymentMethod) . '_form';
        $this->selectOption($this->getLocator()->payment->$paymentMethodForm, $this->getLocator()->payment->$paymentMethodName);
        if ($this->isRedirectPaymentMethod($paymentMethod)) {
            $this->proceedWithPayment($paymentMethod);
        }
    }

    /**
     * @param String $paymentMethod
     * @return mixed
     * @throws ExceptionAlias
     */
    public function proceedWithPayment($paymentMethod): void
    {
        if ($paymentMethod !== '') {
            $this->preparedClick($this->getLocator()->payment->place_order);
        }
    }

    /**
     * @param String $minPurchaseSum
     * @throws ExceptionAlias
     */
    public function fillBasket($minPurchaseSum): void
    {
        parent::fillBasket($minPurchaseSum);
        $this->waitForText('You added');
    }

    /**
     * @param string $customerType
     * @throws ExceptionAlias
     */
    public function fillCustomerDetails($customerType): void
    {
        $this->fillUnregisteredCustomerDetails($customerType);
        $this->fillBillingDetails($customerType);
    }

    /**
     * @param $customerType
     * @throws ExceptionAlias
     */
    public function fillMandatoryCustomerData($customerType)
    {
        $this->preparedFillField($this->getLocator()->checkout->email_address, $this->getCustomer($customerType)->getEmailAddress());
        $this->preparedFillField($this->getLocator()->checkout->first_name, $this->getCustomer($customerType)->getFirstName());
        $this->preparedFillField($this->getLocator()->checkout->last_name, $this->getCustomer($customerType)->getLastName());
    }

    /**
     *
     * @param $customerType
     * @throws ExceptionAlias
     */
    public function fillBillingDetails($customerType)
    {

        $this->preparedFillField($this->getLocator()->checkout->street_address, $this->getCustomer($customerType)->getStreetAddress());
        $this->preparedFillField($this->getLocator()->checkout->town, $this->getCustomer($customerType)->getTown());
        $this->preparedFillField($this->getLocator()->checkout->post_code, $this->getCustomer($customerType)->getPostCode());
        $this->preparedFillField($this->getLocator()->checkout->phone, $this->getCustomer($customerType)->getPhone());
        $this->selectOption($this->getLocator()->checkout->country, $this->getCustomer($customerType)->getCountry());
        $this->wait(10);
        $this->preparedClick($this->getLocator()->checkout->next, 60);
        $this->waitUntil(60, [$this, 'waitUntilPageLoaded'], [$this->getLocator()->page->payment]);

    }

    /**
     * @param string $customerType
     * @throws ExceptionAlias
     */
    public function fillUnregisteredCustomerDetails($customerType)
    {
        if ($customerType !== static::REGISTERED_CUSTOMER) {
            $this->fillMandatoryCustomerData($customerType);
        }
    }

//    /**
//     * @throws ExceptionAlias
//     */
//    public function logIn()
//    {
//        $this->amOnPage($this->getLocator()->page->sign_in);
//        if (!$this->isCustomerSignedIn()) {
//            $this->preparedFillField($this->getLocator()->sign_in->email, $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress());
//            $this->preparedFillField($this->getLocator()->sign_in->password, $this->getCustomer(static::REGISTERED_CUSTOMER)->getPassword());
//            $this->preparedClick($this->getLocator()->sign_in->sign_in, 60);
//        }
//    }

    /**
     * @return array
     */
    public function getPaymentMethodConfigurationNameExceptions(): array
    {
        return $this->paymentMethodConfigurationNameExceptions;
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     */
    public function validateTransactionInDatabase($paymentMethod, $paymentAction): void
    {
        exec("docker exec -it " . getenv("MAGENTO_CONTAINER_NAME") . " /usr/local/bin/php /var/www/html/bin/magento cron:run");
        parent::validateTransactionInDatabase($paymentMethod, $paymentAction);
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
     * @return array
     */
    public function getMappedPaymentActions(): array
    {
        return $this->mappedPaymentActions;
    }

    /**
     * @param $paymentMethod
     * @return string
     */
    private function getActingPaymentMethod($paymentMethod): string
    {
        if (strcasecmp($paymentMethod, static::CREDIT_CARD_ONE_CLICK) === 0) {
            return 'CreditCard';
        }
        return $paymentMethod;
    }

    /**
     * @return bool
     */
    private function isCustomerSignedIn(): bool
    {
        $this->wait(1);
        $currentUrl = $this->grabFromCurrentUrl();
        //otherwise we are already signed in
        return strpos($currentUrl, $this->getLocator()->page->my_account) !== false;
    }

}
