<?php

namespace Step\Acceptance\PaymentMethod;

use Step\Acceptance\iPerformFillPaymentFields;
use Step\Acceptance\iPerformPayment;
use Exception;

class SofortStep extends GenericPaymentMethodStep implements iPerformPayment, iPerformFillPaymentFields
{
    const STEP_NAME = 'Sofort';

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop() : void
    {
        $this->waitForElementVisible($this->getLocator()->bank_name_field);
        $this->preparedFillField(
            $this->getLocator()->bank_name_field,
            $this->getPaymentMethod()->getDemoBank(),
            60
        );
        $this->preparedClick(
            $this->getLocator()->bank_button_weiter,
            60
        );
        $this->waitForElementVisible($this->getLocator()->kontonummer_field);
        $this->waitForElementVisible($this->getLocator()->pin_field);
        $this->preparedFillField(
            $this->getLocator()->kontonummer_field,
            $this->getPaymentMethod()->getKontonummer(),
            60
        );
        $this->preparedFillField(
            $this->getLocator()->pin_field,
            $this->getPaymentMethod()->getPin(),
            60
        );
        $this->preparedClick(
            $this->getLocator()->login_button_weiter,
            60
        );
        $this->waitForElementVisible($this->getLocator()->select_account_button_weiter);
        $this->preparedClick(
            $this->getLocator()->select_account_button_weiter,
            60
        );
        $this->waitForElementVisible($this->getLocator()->tan_field, 60);
        $this->preparedFillField(
            $this->getLocator()->tan_field,
            $this->getPaymentMethod()->getTan(),
            60
        );
        $this->preparedClick(
            $this->getLocator()->provide_tan_button_weiter,
            60
        );
    }

    /**
     * @throws Exception
     */
    public function fillFieldsInTheShop()
    {
        $this->waitForElementVisible($this->getLocator()->bank_name_field);
        $this->preparedFillField(
            $this->getLocator()->bank_name_field,
            $this->getPaymentMethod()->getDemoBank(),
            60
        );
        $this->preparedClick(
            $this->getLocator()->bank_button_weiter,
            60
        );
    }
}
