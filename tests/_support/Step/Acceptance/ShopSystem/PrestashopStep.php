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
class PrestashopStep extends GenericShopSystemStep implements iConfigurePaymentMethod, iPrepareCheckout, iValidateSuccess
{
    const STEP_NAME = 'Prestashop';

    const SETTINGS_TABLE_NAME = 'ps_configuration';

    const NAME_COLUMN_NAME = 'name';

    const VALUE_COLUMN_NAME = 'value';

    const PAYMENT_METHOD_PREFIX = 'WIRECARD_PAYMENT_GATEWAY_';

    const TRANSACTION_TABLE_NAME = 'ps_wirecard_payment_gateway_tx';

    const TRANSACTION_TYPE_COLUMN_NAME = 'transaction_type';

    const DEFAULT_COUNTRY_OPTION_NAME = 'PS_COUNTRY_DEFAULT';

    const CURRENCY_OPTION_NAME = 'PS_CURRENCY_DEFAULT';

    const CREDIT_CARD_ONE_CLICK_CONFIGURATION_OPTION = 'cc_vault_enabled';

    const CUSTOMER_TABLE = 'ps_customer';

    const CUSTOMER_IS_GUEST_COLUMN_NAME = 'is_guest';

    const CUSTOMER_EMAIL_COLUMN_NAME = 'email';

    /**
     * @var array
     */
    private $paymentMethodConfigurationNameExceptions =
        [
            'cc_vault_enabled' => 'ccvault_enabled'
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
            $fullName = self::PAYMENT_METHOD_PREFIX . strtoupper($actingPaymentMethod) . '_' . strtoupper($name);
            $this->putValueInDatabase($fullName, $value);
        }
    }

    /**
     * @param String $currency
     * @param String $defaultCountry
     * @throws ExceptionAlias
     */
    public function configureShopSystemCurrencyAndCountry($currency, $defaultCountry): void
    {
        $moduleID = $this->grabFromDatabase('ps_module', 'id_module', ['name' => 'wirecardpaymentgateway']);
        //countries are taken from ps_country table by numbers
        $countryID = $this->grabFromDatabase('ps_country', 'id_country', ['iso_code' => $defaultCountry]);
        //currencies are taken from ps_currency table by numbers
        $currencyID = $this->grabFromDatabase('ps_currency', 'id_currency', ['iso_code' => $currency]);
        $this->updateInDatabase('ps_country', ['active' => '1'], ['iso_code' => $defaultCountry]);
        //payment modules needs to be activated for specific country
        $this->updateInDatabase('ps_module_country', ['id_country' => $countryID], ['id_module' => $moduleID]);
        parent::configureShopSystemCurrencyAndCountry($currencyID, $countryID);
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
            $this->preparedFillField($this->getLocator()->checkout->password, $this->getCustomer(static::REGISTERED_CUSTOMER)->getPassword());
            $this->checkOption($this->getLocator()->checkout->agree_to_terms_and_conditions_and_privacy_policy);
            $this->preparedClick($this->getLocator()->register->save);
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
        $this->waitForText('Product successfully added to your shopping cart');
    }

    /**
     * @param string $customerType
     * @throws ExceptionAlias
     */
    public function fillCustomerDetails($customerType): void
    {
        $this->fillUnregisteredCustomerDetails($customerType);
        if ($customerType === static::REGISTERED_CUSTOMER) {
            $this->preparedClick($this->getLocator()->checkout->continue_confirm_address);
        }
        $this->fillBillingDetails($customerType);
    }

    /**
     * @param $customerType
     * @throws ExceptionAlias
     */
    public function fillMandatoryCustomerData($customerType)
    {
        $this->selectOption($this->getLocator()->checkout->social_title, '1');
        $this->preparedFillField($this->getLocator()->checkout->first_name, $this->getCustomer($customerType)->getFirstName());
        $this->preparedFillField($this->getLocator()->checkout->last_name, $this->getCustomer($customerType)->getLastName());
        $this->preparedFillField($this->getLocator()->checkout->email_address, $this->getCustomer($customerType)->getEmailAddress());
    }

    /**
     *
     * @param $customerType
     * @throws ExceptionAlias
     */
    public function fillBillingDetails($customerType) : void
    {
        try {
            parent::fillBillingDetails($customerType);
            $this->selectOption($this->getLocator()->checkout->country, $this->getCustomer($customerType)->getCountry());
            $this->preparedClick($this->getLocator()->checkout->continue_confirm_address);
        } catch (NoSuchElementException $e) {
            //this means the address has already been saved
        }
        //this button should appear on the next page, so wait till we see it
        $this->preparedClick($this->getLocator()->checkout->continue_confirm_delivery, 60);
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
