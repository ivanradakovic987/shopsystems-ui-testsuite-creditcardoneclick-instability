<?php

namespace Step\Acceptance\ShopSystem;

/**
 * Class WoocommerceActor
 * @package Helper\Actor
 */

use Step\Acceptance\iConfigurePaymentMethod;
use Step\Acceptance\iPrepareCheckout;
use Step\Acceptance\iValidateSuccess;
use Exception;

/**
 * Class WoocommerceStep
 * @package Step\Acceptance
 */
class WoocommerceStep extends GenericShopSystemStep implements iConfigurePaymentMethod, iPrepareCheckout, iValidateSuccess
{
    /**
     *
     */
    public const STEP_NAME = 'woocommerce';
    /**
     *
     */
    public const SETTINGS_TABLE_NAME = 'wp_options';
    /**
     *
     */
    public const NAME_COLUMN_NAME = 'option_name';
    /**
     *
     */
    public const VALUE_COLUMN_NAME = 'option_value';
    /**
     *
     */
    public const TRANSACTION_TABLE_NAME = 'wp_wirecard_payment_gateway_tx';
    /**
     *
     */
    public const WIRECARD_OPTION_NAME = 'woocommerce_wirecard_ee_';
    /**
     *
     */
    public const CURRENCY_OPTION_NAME = 'woocommerce_currency';
    /**
     *
     */
    public const DEFAULT_COUNTRY_OPTION_NAME = 'woocommerce_default_country';

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

        $optionName = self::WIRECARD_OPTION_NAME . strtolower($paymentMethod) . '_settings';
        $optionValue = serialize($this->buildPaymentMethodConfig($paymentMethod, $paymentAction, $this->getMappedPaymentActions(), $this->getGateway()));

        $this->putValueInDatabase($optionName, $optionValue);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function fillCustomerDetails()
    {
        //woocommerce is dynamically loading possible payment methods while filling form, so we need to make sure all elements are fillable or clickable
        $this->preparedFillField($this->getLocator()->checkout->first_name, $this->getCustomer()->getFirstName());
        $this->preparedFillField($this->getLocator()->checkout->last_name, $this->getCustomer()->getLastName());
        $this->preparedClick($this->getLocator()->checkout->country);
        $this->preparedFillField($this->getLocator()->checkout->country_entry, $this->getCustomer()->getCountry());
        $this->preparedClick($this->getLocator()->checkout->country_entry_selected);
        $this->preparedFillField($this->getLocator()->checkout->street_address, $this->getCustomer()->getStreetAddress());
        $this->preparedFillField($this->getLocator()->checkout->town, $this->getCustomer()->getTown());
        $this->preparedFillField($this->getLocator()->checkout->post_code, $this->getCustomer()->getPostCode());
        $this->preparedFillField($this->getLocator()->checkout->phone, $this->getCustomer()->getPhone());
        $this->preparedFillField($this->getLocator()->checkout->email_address, $this->getCustomer()->getEmailAddress());
    }


    /**
     * @param string $paymentMethod
     * @return mixed
     * @throws Exception
     */
    public function startPayment($paymentMethod)
    {
        $this->wait(2);
        $this->preparedClick($this->getLocator()->checkout->place_order);
        if (!$this->isRedirectPaymentMethod($paymentMethod)) {
            $this->startCreditCardPayment($paymentMethod);
        }
    }

    /**
     * @param $paymentMethod
     * @return mixed
     * @throws Exception
     */
    public function proceedWithPayment($paymentMethod)
    {
        if (!$this->isRedirectPaymentMethod($paymentMethod)) {
            $this->preparedClick($this->getLocator()->order_pay->pay);
        }
    }

    /**
     * @param $paymentMethod
     * @throws Exception
     */
    public function startCreditCardPayment($paymentMethod)
    {
        $paymentMethodForm = strtolower($paymentMethod) . '_form';
        $this->waitForElementVisible($this->getLocator()->checkout->$paymentMethodForm);
        $this->scrollTo($this->getLocator()->checkout->$paymentMethodForm);
    }

}