<?php

namespace Step\Acceptance\ShopSystem;

use Step\Acceptance\iConfigurePaymentMethod;
use Step\Acceptance\iPrepareCheckout;
use Step\Acceptance\iValidateSuccess;

use Exception as ExceptionAlias;

/**
 * Class PrestashopStep
 * @package Step\Acceptance|ShopSystem
 */
class PrestashopStep extends GenericShopSystemStep implements iConfigurePaymentMethod, iPrepareCheckout, iValidateSuccess
{
    const STEP_NAME = 'prestashop';

    const SETTINGS_TABLE_NAME = 'ps_configuration';

    const NAME_COLUMN_NAME = 'name';

    const VALUE_COLUMN_NAME = 'value';

    const PAYMENT_METHOD_PREFIX = 'WIRECARD_PAYMENT_GATEWAY_';

    const TRANSACTION_TABLE_NAME = 'ps_wirecard_payment_gateway_tx';

    const DEFAULT_COUNTRY_OPTION_NAME = 'PS_COUNTRY_DEFAULT';

    const CURRENCY_OPTION_NAME = 'PS_CURRENCY_DEFAULT';

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
     * @throws Exception
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
    {
        $db_config = $this->buildPaymentMethodConfig($paymentMethod, $paymentAction, $this->getMappedPaymentActions(), $this->getGateway());
        foreach ($db_config as $name => $value) {
            //some configuration options are different if different shops, this is handling the differences
            if (array_key_exists($name, $this->getPaymentMethodConfigurationNameExceptions())) {
                $name = $this->getPaymentMethodConfigurationNameExceptions()[$name];
            }
            $fullName = self::PAYMENT_METHOD_PREFIX . strtoupper($paymentMethod) . '_' . strtoupper($name);
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
     * @param String $paymentMethod
     * @return mixed
     * @throws ExceptionAlias
     */
    public function startPayment($paymentMethod)
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
    public function proceedWithPayment($paymentMethod)
    {
        $this->checkOption($this->getLocator()->checkout->agree_with_terms_of_service);
        $this->preparedClick($this->getLocator()->checkout->order_with_obligation_to_pay);
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
     * @return mixed
     * @throws ExceptionAlias
     */
    public function fillCustomerDetails()
    {
        $this->selectOption($this->getLocator()->checkout->social_title, '1');
        $this->preparedFillField($this->getLocator()->checkout->first_name, $this->getCustomer()->getFirstName());
        $this->preparedFillField($this->getLocator()->checkout->last_name, $this->getCustomer()->getLastName());
        $this->preparedFillField($this->getLocator()->checkout->email_address, $this->getCustomer()->getEmailAddress());
        $this->checkOption($this->getLocator()->checkout->agree_to_terms_and_conditions_and_privacy_policy);
        $this->preparedClick($this->getLocator()->checkout->continue);
        $this->fillBillingDetails();
    }

    /**
     *
     * @throws ExceptionAlias
     */
    public function fillBillingDetails()
    {
        //if the first field is there, means the others too, so no need to prepare (it's faster)
        $this->preparedFillField($this->getLocator()->checkout->street_address, $this->getCustomer()->getStreetAddress());
        $this->preparedFillField($this->getLocator()->checkout->town, $this->getCustomer()->getTown());
        $this->preparedFillField($this->getLocator()->checkout->post_code, $this->getCustomer()->getPostCode());
        $this->preparedFillField($this->getLocator()->checkout->phone, $this->getCustomer()->getPhone());
        $this->selectOption($this->getLocator()->checkout->country, $this->getCustomer()->getCountry());
        $this->preparedClick($this->getLocator()->checkout->continue2);
        //this button should appear on the next page, so wait till we see it
        $this->preparedClick($this->getLocator()->checkout->continue3, 60);
    }

    /**
     * @return array
     */
    public function getPaymentMethodConfigurationNameExceptions(): array
    {
        return $this->paymentMethodConfigurationNameExceptions;
    }

}