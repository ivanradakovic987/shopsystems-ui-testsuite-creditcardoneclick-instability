<?php

namespace Step\Acceptance;

/**
 * Interface iPerformPayment
 */
interface iPerformPayment {
    /**
     * @return mixed
     */
    public function performPaymentActionsInTheShop();

    /**
     * @return mixed
     */
    public function performPaymentMethodActionsOutsideShop();
}