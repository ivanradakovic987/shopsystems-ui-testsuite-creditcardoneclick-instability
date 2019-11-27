<?php

namespace Step\Acceptance;

/**
 * Interface iValidateSuccess
 * @package Helper
 */
interface iValidateSuccess
{
    /**
     * @return mixed
     */
    public function validateSuccessPage();

    /**
     * @param $paymentMethod
     * @param $paymentAction
     * @return mixed
     */
    public function validateTransactionInDatabase($paymentMethod, $paymentAction);
}