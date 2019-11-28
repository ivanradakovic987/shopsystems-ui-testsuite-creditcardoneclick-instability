<?php

namespace Step\Acceptance\PaymentMethod;

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
     * @var string
     */
    private $wirecardFrameSelector = '#wirecard-integrated-payment-page-frame';

    /**
     * @return mixed
     * @throws Exception
     */
    public function performPaymentActionsInTheShop()
    {
        $this->switchFrame();
        $this->preparedFillField($this->getLocator()->last_name, $this->getCreditCard()->getLastName());
        $this->fillField($this->getLocator()->card_number, $this->getCreditCard()->getCardNumber());
        $this->fillField($this->getLocator()->cvv, $this->getCreditCard()->getCvv());
        $this->fillField($this->getLocator()->expiry_date, $this->getCreditCard()->getValidUntil());
        $this->switchToIFrame();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function goThroughExternalFlow()
    {
        $this->preparedFillField($this->getLocator()->password, $this->getCreditCard()->getPassword());
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