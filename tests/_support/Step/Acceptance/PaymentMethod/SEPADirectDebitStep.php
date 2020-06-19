<?php

namespace Step\Acceptance\PaymentMethod;

use Step\Acceptance\iPerformFillPaymentFields;
use Exception;
use Step\Acceptance\iPerformPaymentInsideTheShop;

/**
 * Class SEPADirectDebitStep
 * @package Step\Acceptance\PaymentMethod
 */
class SEPADirectDebitStep extends GenericPaymentMethodStep implements
    iPerformFillPaymentFields,
    iPerformPaymentInsideTheShop
{
    const STEP_NAME = 'SEPADirectDebit';

    /**
     * @throws Exception
     */
    public function fillFieldsInTheShop(): void
    {
        $this->preparedFillField($this->getLocator()->first_name, $this->getPaymentMethod()->getFirstName());
        $this->preparedFillField($this->getLocator()->last_name, $this->getPaymentMethod()->getLastName());
        $this->preparedFillField($this->getLocator()->iban, $this->getPaymentMethod()->getIban());
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function performAdditionalPaymentStepsInsideTheShop()
    {
        // wait for all elements to be clickable before proceeding
        $this->waitForElementVisible($this->getLocator()->dialog, 60);
        $this->waitForElementClickable($this->getLocator()->terms_and_conditions, 60);
        $this->waitForElementClickable($this->getLocator()->confirm_button, 60);

        $this->preparedCheckOption($this->getLocator()->terms_and_conditions);
        $this->preparedClick($this->getLocator()->confirm_button);
    }
}
