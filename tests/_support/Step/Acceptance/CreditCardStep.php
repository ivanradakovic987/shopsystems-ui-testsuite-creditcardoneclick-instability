<?php

namespace Step\Acceptance;

use Codeception\Scenario;
use Exception;

class CreditCardStep extends GenericStep implements iPerformPayment
{
    private $wirecardFrameSelector = '#wirecard-integrated-payment-page-frame';

    public function __construct(Scenario $scenario)
    {
        parent::__construct($scenario);
        $this->setStepName('CreditCard');
        $this->setLocator($this->getDataFromDataFile(PAYMENT_METHOD_LOCATOR_FOLDER_PATH . $this->getStepName() . DIRECTORY_SEPARATOR . $this->getStepName() . 'Locators.json'));
    }

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
        $this->wait(2);
        //get wirecard seemless frame name
        $wirecardFrameName = $this->executeJS('return document.querySelector("#' . $this->getLocator()->frame . '").getAttribute("name")');
        $this->switchToIFrame($wirecardFrameName);
    }
}