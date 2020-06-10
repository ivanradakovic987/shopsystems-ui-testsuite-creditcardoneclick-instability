<?php

namespace Step\Acceptance\PaymentMethod;

use Step\Acceptance\iPerformFillPaymentFields;
use Step\Acceptance\iPerformPayment;
use Exception;

class IdealStep extends GenericPaymentMethodStep implements iPerformPayment, iPerformFillPaymentFields
{
    const STEP_NAME = 'iDEAL';

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop() : void
    {
        $this->preparedClick($this->getLocator()->confirm_transaction, 60);
    }

    /**
     * @throws Exception
     */
    public function fillFieldsInTheShop()
    {
        $this->waitForElementVisible($this->getLocator()->ideal_bank_select);
        $this->preparedSelectOption(
            $this->getLocator()->ideal_bank_select,
            $this->getPaymentMethod()->getBank()
        );
    }
}
