<?php

namespace Step\Acceptance;

/**
 * Interface iPrepareCheckout
 * @package Helper
 */
interface iPrepareCheckout
{
    /**
     * @param $purchaseSum
     * @return mixed
     */
    public function fillBasket($purchaseSum);

    /**
     * @return mixed
     */
    public function goToCheckout();

    /**
     * @return mixed
     */
    public function fillCustomerDetails();

    /**
     * @param string $paymentMethod
     * @return mixed
     */
    public function startPayment($paymentMethod);

    /**
     * @param $paymentMethod
     * @return mixed
     */
    public function proceedWithPayment($paymentMethod);
}