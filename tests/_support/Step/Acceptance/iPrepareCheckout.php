<?php

namespace Step\Acceptance;

/**
 * Interface iPrepareCheckout
 * @package Helper
 */
interface iPrepareCheckout
{
    //add needed items to the basket
    /**
     * @param $purchaseSum
     * @return mixed
     */
    public function fillBasket($purchaseSum);
    //go to checkout

    /**
     * @return mixed
     */
    public function goToCheckout();
    //fill in all customer details

    /**
     * @return mixed
     */
    public function fillCustomerDetails();
    //in some shopsystems there is a need to press "pay" button to start payment

    /**
     * @return mixed
     */
    public function startPayment();

    /**
     * @return mixed
     */
    public function proceedWithPayment();
}