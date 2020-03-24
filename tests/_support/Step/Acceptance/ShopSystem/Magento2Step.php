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

    const PAYMENT_METHOD_PREFIX = 'payment/wirecard_elasticengine_';

    const TRANSACTION_TABLE_NAME = 'ps_wirecard_payment_gateway_tx';

    const DEFAULT_COUNTRY_OPTION_NAME = 'general/country/default';

    const CURRENCY_OPTION_NAME = 'currency/options/base';

    const CREDIT_CARD_ONE_CLICK_CONFIGURATION_OPTION = 'cc_vault_enabled';

    const CUSTOMER_TABLE = 'customer_entity';

    const CUSTOMER_EMAIL_COLUMN_NAME = 'email';




    /**
     * @var array
     */
    private $paymentMethodConfigurationNameExceptions =
        [
            'cc_vault_enabled' => 'cc_vault/active',
            'enabled' => 'active',
            'purchase' => 'authorize_capture'
        ];

    /**
     * @param String $paymentMethod
     * @param String $paymentAction
     * @return mixed|void
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
    {
        $actingPaymentMethod = $this->getActingPaymentMethod($paymentMethod);
        $db_config = $this->buildPaymentMethodConfig($actingPaymentMethod, $paymentAction, $this->getMappedPaymentActions(), $this->getGateway());
        if (strcasecmp($paymentMethod, static::CREDIT_CARD_ONE_CLICK) === 0) {
            //CreditCard One click is not a separate payment method but a configuration of CreditCard
            $db_config[self::CREDIT_CARD_ONE_CLICK_CONFIGURATION_OPTION] = '1';
        }
        foreach ($db_config as $name => $value) {
            //some configuration options are different if different shops, this is handling the differences
            if (array_key_exists($name, $this->paymentMethodConfigurationNameExceptions)) {
                $name = $this->paymentMethodConfigurationNameExceptions[$name];
            }
            if (array_key_exists($value, $this->paymentMethodConfigurationNameExceptions)) {
                $value = $this->paymentMethodConfigurationNameExceptions[$name];
            }
            $fullName = self::PAYMENT_METHOD_PREFIX . strtolower($actingPaymentMethod) . '/' . strtolower($name);
            $this->putValueInDatabase($fullName, $this->convertWordValueToBinaryString($value));
        }
        $this->pause();
    }

    /**
     * @return mixed
     * @throws ExceptionAlias
     */
    public function registerCustomer(): void
    {
        if (!$this->isCustomerRegistered()) {
            $this->amOnPage($this->getLocator()->page->register);
            $this->fillMandatoryCustomerData(static::REGISTERED_CUSTOMER);
            $this->preparedFillField($this->getLocator()->register->password, $this->getCustomer(static::REGISTERED_CUSTOMER)->getPassword());
            $this->preparedFillField($this->getLocator()->register->confirm_password, $this->getCustomer(static::REGISTERED_CUSTOMER)->getPassword());
            $this->preparedClick($this->getLocator()->register->create_an_account);
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
        $this->selectOption($this->getLocator()->checkout->$paymentMethodForm, $this->getLocator()->checkout->$paymentMethodName);
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
            $this->checkOption($this->getLocator()->checkout->agree_with_terms_of_service);
            $this->preparedClick($this->getLocator()->checkout->order_with_obligation_to_pay);
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
//        if ($customerType === static::REGISTERED_CUSTOMER) {
//            $this->preparedClick($this->getLocator()->checkout->continue_confirm_address);
//        }
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
//        try {
            $this->preparedFillField($this->getLocator()->checkout->street_address, $this->getCustomer($customerType)->getStreetAddress());
            $this->preparedFillField($this->getLocator()->checkout->town, $this->getCustomer($customerType)->getTown());
            $this->preparedFillField($this->getLocator()->checkout->post_code, $this->getCustomer($customerType)->getPostCode());
            $this->preparedFillField($this->getLocator()->checkout->phone, $this->getCustomer($customerType)->getPhone());
            $this->selectOption($this->getLocator()->checkout->country, $this->getCustomer($customerType)->getCountry());
            $this->preparedClick($this->getLocator()->checkout->continue_confirm_address);
//        } catch (NoSuchElementException $e) {
//            //this means the address has already been saved
//        }
        //this button should appear on the next page, so wait till we see it
        $this->preparedClick($this->getLocator()->checkout->bext, 60);
    }

    /**
     * @param string $customerType
     * @throws ExceptionAlias
     */
    public function fillUnregisteredCustomerDetails($customerType)
    {
        if ($customerType !== static::REGISTERED_CUSTOMER) {
            $this->fillMandatoryCustomerData($customerType);
            $this->checkOption($this->getLocator()->checkout->agree_to_terms_and_conditions_and_privacy_policy);
            $this->preparedClick($this->getLocator()->checkout->continue);
        }
    }

    /**
     * @throws ExceptionAlias
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
     * @return array
     */
    public function getPaymentMethodConfigurationNameExceptions(): array
    {
        return $this->paymentMethodConfigurationNameExceptions;
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
