<?php

namespace Step\Acceptance\PaymentMethod;

use Step\Acceptance\iPerformFillPaymentFields;
use Step\Acceptance\iPerformPayment;
use Exception;

class GiropayStep extends GenericPaymentMethodStep implements iPerformPayment, iPerformFillPaymentFields
{
    const STEP_NAME = 'giropay';

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop() : void
    {
        $this->preparedFillField(
            $this->getLocator()->sc_field,
            $this->getPaymentMethod()->getSc()
        );
        $this->preparedFillField(
            $this->getLocator()->extension_sc_field,
            $this->getPaymentMethod()->getExtensionSc()
        );
        $this->preparedFillField(
            $this->getLocator()->customer_name_field,
            $this->getPaymentMethod()->getCustomerName()
        );
        $this->preparedFillField(
            $this->getLocator()->customer_iban_field,
            $this->getPaymentMethod()->getCustomerIban()
        );
        $this->preparedClick($this->getLocator()->absenden_button, 60);
    }

    /**
     * @throws Exception
     */
    public function fillFieldsInTheShop()
    {
        $this->waitForElementVisible($this->getLocator()->bic_field);
        $this->preparedFillField(
            $this->getLocator()->bic_field,
            $this->getPaymentMethod()->getBic()
        );
    }
}
