<?php

namespace Step\Acceptance\PaymentMethod;

use Step\Acceptance\iPerformPayment;
use Exception;

class IdealStep extends GenericPaymentMethodStep implements iPerformPayment
{
    const STEP_NAME = 'iDEAL';

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop() : void
    {
        $this->preparedClick($this->getLocator()->confirm_transaction, 60);
    }
}
