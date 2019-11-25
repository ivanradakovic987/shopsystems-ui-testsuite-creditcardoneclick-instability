<?php

namespace Step\Acceptance;

/**
 * Class PrestashopActor
 * @package Helper\Actor
 */
class PrestashopStep extends GenericStep implements iConfigurePaymentMethod, iValidateSuccess
{
    /**
     *
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
 {
     // TODO: Implement configureCredentials() method.
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
    public function validateTransactionInDatabase()
    {
        // TODO: Implement validateTransactionInDatabase() method.
    }
}