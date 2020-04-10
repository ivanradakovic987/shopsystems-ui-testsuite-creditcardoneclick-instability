<?php

namespace Step\Acceptance\PaymentMethod;

use Facebook\WebDriver\Exception\TimeOutException;
use Step\Acceptance\iPerformFillPaymentFields;
use Step\Acceptance\iPerformPayment;
use Exception;

/**
 * Class CreditCardStep
 * @package Step\Acceptance\PaymentMethod
 */
class CreditCardStep extends GenericPaymentMethodStep implements iPerformPayment, iPerformFillPaymentFields
{
    const STEP_NAME = 'CreditCard';

    /**
     * @throws Exception
     */
    public function fillFieldsInTheShop(): void
    {
        $this->switchToCreditCardUIFrame();
        try {
            $this->preparedFillField($this->getLocator()->last_name, $this->getPaymentMethod()->getLastName(), 60);
        } catch (TimeOutException $e) {
            $this->switchToIFrame();
            $this->wait(5);
            $this->switchToCreditCardUIFrame();
            $this->preparedFillField($this->getLocator()->last_name, $this->getPaymentMethod()->getLastName(), 60);
        }
        $this->fillField($this->getLocator()->card_number, $this->getPaymentMethod()->getCardNumber());
        $this->fillField($this->getLocator()->cvv, $this->getPaymentMethod()->getCvv());
        $this->fillField($this->getLocator()->expiry_date, $this->getPaymentMethod()->getValidUntil());
        $this->switchToIFrame();
    }

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop() : void
    {
        $this->preparedFillField($this->getLocator()->password, $this->getPaymentMethod()->getPassword());
        $this->click($this->getLocator()->continue_button);
    }

    /**
     * Method switchToCreditCardUIFrame
     */
    public function switchToCreditCardUIFrame()
    {
        //wait for Javascript to load iframe and it's contents
        $this->wait(5);
        //get wirecard seemless frame name
        $wirecardFrameName = $this->executeJS('return document.querySelector("#' . $this->getLocator()->frame . '").getAttribute("name")');
        $this->switchToIFrame($wirecardFrameName);
    }
}