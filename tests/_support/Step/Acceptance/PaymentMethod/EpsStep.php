<?php

namespace Step\Acceptance\PaymentMethod;

use Step\Acceptance\iPerformFillPaymentFields;
use Step\Acceptance\iPerformPayment;
use Exception;

/**
 * Class CreditCardStep
 * @package Step\Acceptance\PaymentMethod
 */
class EpsStep extends GenericPaymentMethodStep implements iPerformPayment, iPerformFillPaymentFields
{
    const STEP_NAME = 'Eps';

    /**
     * @throws Exception
     */
    public function fillFieldsInTheShop(): void
    {
        $this->performAdditionalCheckoutActions();
    }

    /**
     * @throws Exception
     */
    public function performAdditionalCheckoutActions(): void
    {
        $this->preparedFillField($this->getLocator()->bic, $this->getPaymentMethod()->getBic());
    }

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop() : void
    {
        $this->preparedClick($this->getLocator()->login, 60);
        $this->preparedClick($this->getLocator()->auftrag_zeichnen, 60);
        $this->preparedClick($this->getLocator()->als_einzelbuchung_zeichnen, 60);
        $this->preparedClick($this->getLocator()->ok, 60);
        $this->preparedClick($this->getLocator()->zurück, 60);
    }
}
