<?php

namespace Step\Acceptance;
/**
 * Interface iConfigurePaymentMethod
 * @package Helper\Interface
 */
interface iConfigurePaymentMethod
{

    /**
     * @param $paymentMethod
     * @param $paymentAction
     * @return mixed
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction);

}