<?php

namespace Step\Acceptance\PaymentMethod;

use Step\Acceptance\iPerformFillPaymentFields;
use Exception;

/**
 * Class GuaranteedInvoiceStep
 * @package Step\Acceptance\PaymentMethod
 */
class GuaranteedInvoiceStep extends GenericPaymentMethodStep implements iPerformFillPaymentFields
{
    const STEP_NAME = 'GuaranteedInvoice';

    /**
     * @throws Exception
     */
    public function fillFieldsInTheShop(): void
    {
        $this->performAdditionalCheckoutActions();
    }

    /**
     * Method picks date of birth and checks terms and conditions on checkout page
     * @throws Exception
     */
    public function performAdditionalCheckoutActions(): void
    {
        $this->preparedClick($this->getLocator()->date_of_birth);
        $this->pressKey($this->getLocator()->date_of_birth, $this->getPaymentMethod()->getDateOfBirth());
        $this->checkOption($this->getLocator()->terms_and_conditions);
    }
}
