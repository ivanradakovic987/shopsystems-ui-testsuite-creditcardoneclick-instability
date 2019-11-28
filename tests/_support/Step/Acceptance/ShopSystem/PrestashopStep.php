<?php

namespace Step\Acceptance\ShopSystem;

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
    public const TRANSACTION_TABLE_NAME = 'wp_wirecard_payment_gateway_tx';
    /**
     *
     */
    public const WIRECARD_OPTION_NAME = 'woocommerce_wirecard_ee_';

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
//TODO: implement correct database
        //        $db_config = $this->buildPaymentMethodConfig($paymentMethod, $paymentAction, $this->getMappedPaymentActions(), $this->getGateway());
//        foreach ($db_config as $name => $value) {
//            $fullName = self::PAYMENT_METHOD_PREFIX . strtoupper($name);
//            $this->putValueInDatabase($fullName, $value);
//        }
    }

    /**
     * @param $currency
     * @param $country
     */
    public function configureShopSystemCurrencyAndCountry($currency, $country): void
    {
        //TODO: remove this or redefine
    }

    /**
     *
     */
    public function validateSuccessPage()
    {
        // TODO: Implement validateSuccessPage() method.
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     */
    public function validateTransactionInDatabase($paymentMethod, $paymentAction)
    {
        // TODO: Implement validateTransactionInDatabase() method.
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
     */
    public function goToCheckout()
    {
        parent::goToCheckout();
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
}