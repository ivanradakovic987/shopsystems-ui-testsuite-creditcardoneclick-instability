<?php

namespace Step\Acceptance;

use Exception;
/**
 * Class PrestashopActor
 * @package Helper\Actor
 */
class PrestashopStep extends GenericShopSystemStep implements iConfigurePaymentMethod, iPrepareCheckout, iValidateSuccess
{
    public const STEP_NAME = 'prestashop';
    public const SETTINGS_TABLE_NAME = 'ps_configuration';
    public const NAME_COLUMN_NAME = 'name';
    public const VALUE_COLUMN_NAME = 'value';
    public const PAYMENT_METHOD_PREFIX = 'WIRECARD_PAYMENT_GATEWAY_';
    public const TRANSACTION_TABLE_NAME = 'wp_wirecard_payment_gateway_tx';
    public const WIRECARD_OPTION_NAME = 'woocommerce_wirecard_ee_';

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
//        $db_config = $this->buildPaymentMethodConfig($paymentMethod, $paymentAction, $this->getMappedPaymentActions(), $this->getGateway());
//        foreach ($db_config as $name => $value) {
//            $fullName = self::PAYMENT_METHOD_PREFIX . strtoupper($name);
//            $this->putValueInDatabase($fullName, $value);
//        }
    }

    /**
     *
     */
    public function validateSuccessPage()
    {
        // TODO: Implement validateSuccessPage() method.
    }

    /**
     *
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
     */
    public function fillCustomerDetails()
    {
        // TODO: Implement fillCustomerDetails() method.
    }

    /**
     * @return mixed
     */
    public function startPayment()
    {
        // TODO: Implement startPayment() method.
    }

    /**
     * @return mixed
     */
    public function proceedWithPayment()
    {
        // TODO: Implement proceedWithPayment() method.
    }
}