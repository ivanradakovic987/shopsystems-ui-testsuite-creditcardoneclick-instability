<?php

namespace Step\Acceptance\PaymentMethod;

use Facebook\WebDriver\Exception\TimeOutException;
use Step\Acceptance\iPerformPayment;
use Exception;

/**
 * Class CreditCardStep
 * @package Step\Acceptance\PaymentMethod
 */
class CreditCardStep extends GenericPaymentMethodStep implements iPerformPayment
{
    /**
     *
     */
    public const STEP_NAME = 'CreditCard';

    /**
     * @return mixed
     * @throws Exception
     */
    public function performPaymentActionsInTheShop()
    {
        $this->switchFrame();
        try
        {
            $this->preparedFillField($this->getLocator()->last_name, $this->getPaymentMethod()->getLastName(), 60);
        } catch (TimeOutException $e) {
            $this->switchToIFrame();
            $this->wait(5);
            $this->switchFrame();
            $this->preparedFillField($this->getLocator()->last_name, $this->getPaymentMethod()->getLastName(), 60);
        }
        $this->fillField($this->getLocator()->card_number, $this->getPaymentMethod()->getCardNumber());
        $this->fillField($this->getLocator()->cvv, $this->getPaymentMethod()->getCvv());
        $this->fillField($this->getLocator()->expiry_date, $this->getPaymentMethod()->getValidUntil());
        $this->switchToIFrame();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function goThroughExternalFlow()
    {
        $this->preparedFillField($this->getLocator()->password, $this->getPaymentMethod()->getPassword());
        $this->click($this->getLocator()->continue_button);
    }

    /**
     * Method switchFrame
     * @since   1.4.4
     */
    public function switchFrame()
    {
        // Switch to Credit Card UI frame

        //wait for Javascript to load iframe and it's contents
        $this->wait(5);
        //get wirecard seemless frame name
        $wirecardFrameName = $this->executeJS('return document.querySelector("#' . $this->getLocator()->frame . '").getAttribute("name")');
        $this->switchToIFrame($wirecardFrameName);
    }
}