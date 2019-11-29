<?php

namespace Step\Acceptance\ShopSystem;

use Codeception\Actor;
use Step\Acceptance\iConfigurePaymentMethod;
use Step\Acceptance\iPrepareCheckout;
use Step\Acceptance\iValidateSuccess;

use Exception;

/**
 * Class PrestashopActor
 * @package Helper\Actor
 */
class PrestashopStep extends GenericShopSystemStep implements iConfigurePaymentMethod, iPrepareCheckout, iValidateSuccess
{
    /**
     *
     */
    public const STEP_NAME = 'prestashop';
    /**
     *
     */
    public const SETTINGS_TABLE_NAME = 'ps_configuration';
    /**
     *
     */
    public const NAME_COLUMN_NAME = 'name';
    /**
     *
     */
    public const VALUE_COLUMN_NAME = 'value';
    /**
     *
     */
    public const PAYMENT_METHOD_PREFIX = 'WIRECARD_PAYMENT_GATEWAY_';
    /**
     *
     */
    public const TRANSACTION_TABLE_NAME = 'ps_wirecard_payment_gateway_tx';
    /**
     *
     */
    public const WIRECARD_OPTION_NAME = 'woocommerce_wirecard_ee_';

    /**
     *
     */
    public const DEFAULT_COUNTRY_OPTION_NAME = 'PS_COUNTRY_DEFAULT';

    /**
     *
     */
    public const CURRENCY_OPTION_NAME = 'PS_CURRENCY_DEFAULT';

    /**
     * @var array
     */
    private $mappedPaymentActions = [
        'CreditCard' => [
            'config' => [
                'row' => 'payment_action'
            ],
            'tx_table' => [
                'authorization' => 'authorization',
                'purchase' => 'purchase'
            ]
        ],
        'PayPal' => [
            'config' => [
                'row' => 'payment_action'
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
        'cc_vault_enabled' => 'ccvault_enabled'
    ];


    /**
     * @return array
     */
    public function getPaymentMethodConfigurationNameExceptions(): array
    {
        return $this->paymentMethodConfigurationNameExceptions;
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
     * @param $paymentAction
     * @return mixed|void
     * @throws Exception
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
    {
        $db_config = $this->buildPaymentMethodConfig($paymentMethod, $paymentAction, $this->getMappedPaymentActions(), $this->getGateway());
        foreach ($db_config as $name => $value) {
            //some configuration options are different if different shops, this is handling the differences
            if (array_key_exists($name, $this->getPaymentMethodConfigurationNameExceptions()))
            {
                $name = $this->getPaymentMethodConfigurationNameExceptions()[$name];
            }
            $fullName = self::PAYMENT_METHOD_PREFIX . strtoupper($paymentMethod) . '_' .  strtoupper($name);
            $this->putValueInDatabase($fullName, $value);
        }
    }

    /**
     * @param $purchaseSum
     * @throws Exception
     */
    public function fillBasket($purchaseSum): void
    {
        parent::fillBasket($purchaseSum);
        $this->waitForText('Product successfully added to your shopping cart');
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function fillCustomerDetails()
    {
        $this->selectOption($this->getLocator()->checkout->social_title, '1');
        $this->preparedFillField($this->getLocator()->checkout->first_name, $this->getCustomer()->getFirstName());
        //if the first field is there, means the others too, so no need to prepare (it's faster)
        $this->fillField($this->getLocator()->checkout->last_name, $this->getCustomer()->getLastName());
        $this->fillField($this->getLocator()->checkout->email_address, $this->getCustomer()->getEmailAddress());
        $this->checkOption($this->getLocator()->checkout->agree_to_terms_and_conditions_and_privacy_policy);
        $this->click($this->getLocator()->checkout->continue);
        $this->fillBillingDetails();
    }

    /**
     *
     */
    public function fillBillingDetails()
    {
        //if the first field is there, means the others too, so no need to prepare (it's faster)
        $this->fillField($this->getLocator()->checkout->street_address, $this->getCustomer()->getStreetAddress());
        $this->fillField($this->getLocator()->checkout->town, $this->getCustomer()->getTown());
        $this->fillField($this->getLocator()->checkout->post_code, $this->getCustomer()->getPostCode());
        $this->fillField($this->getLocator()->checkout->phone, $this->getCustomer()->getPhone());
        $this->selectOption($this->getLocator()->checkout->country, $this->getCustomer()->getCountry());
        $this->click($this->getLocator()->checkout->continue2);
        $this->click($this->getLocator()->checkout->continue3);
    }

    /**
     * @param $paymentMethod
     * @return mixed
     */
    public function startPayment($paymentMethod)

    {
        $paymentMethodName = strtolower($paymentMethod) . '_name';
        $paymentMethodForm = strtolower($paymentMethod) . '_form';
        $this->selectOption($this->getLocator()->checkout->$paymentMethodForm, $this->getLocator()->checkout->$paymentMethodName);
    }

    /**
     * @return mixed
     */
    public function proceedWithPayment()
    {
        $this->checkOption($this->getLocator()->checkout->agree_with_terms_of_service);
        $this->click($this->getLocator()->checkout->order_with_obligation_to_pay);
    }

    /**
     * @param $currency
     * @param $defaultCountry
     * @throws Exception
     */
    public function configureShopSystemCurrencyAndCountry($currency, $defaultCountry): void
    {
        //in prestashop countries are taken from ps_currency table by numbers
        //in prestashop countries are taken from ps_country table by numbers
        $countryID = $this->grabFromDatabase('ps_country', 'id_country', ['iso_code' => $defaultCountry]);
        $currencyID = $this->grabFromDatabase('ps_currency', 'id_currency', ['iso_code' => $currency]);
        $this->updateInDatabase('ps_country', ['active' => '1'], ['iso_code' => $defaultCountry]);

        parent::configureShopSystemCurrencyAndCountry($currencyID, $countryID);
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     */
    public function validateTransactionInDatabase($paymentMethod, $paymentAction): void
    {
        //TODO: implement db polling instead of waiting
        $this->wait(10);
        parent::validateTransactionInDatabase($paymentMethod, $paymentAction);
    }
}