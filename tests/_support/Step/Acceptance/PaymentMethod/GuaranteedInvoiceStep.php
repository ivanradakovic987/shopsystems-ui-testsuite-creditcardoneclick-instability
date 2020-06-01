<?php

namespace Step\Acceptance\PaymentMethod;

use Step\Acceptance\iPerformPayment;
use Exception;

/**
 * Class GuaranteedInvoiceStep
 * @package Step\Acceptance\PaymentMethod
 */
class GuaranteedInvoiceStep extends GenericPaymentMethodStep implements iPerformPayment
{
    const STEP_NAME = 'GuaranteedInvoice';

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop(): void
    {
    }

    /**
     * @throws Exception
     */
    public function performAdditionalCheckoutActions(): void
    {
        $this->preparedFillField($this->getLocator()->date_of_birth, "01/01/1991");
        $this->checkOption($this->getLocator()->terms_and_conditions);
    }
}
