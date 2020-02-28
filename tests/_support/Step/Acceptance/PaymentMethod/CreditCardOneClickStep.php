<?php


namespace Step\Acceptance\PaymentMethod;


use Exception;
use Facebook\WebDriver\Exception\TimeOutException;

class CreditCardOneClickStep extends CreditCardStep
{
    const STEP_NAME = 'CreditCard';

    /**
     * @throws Exception
     */
    public function saveForLaterUse(): void
    {
        $this->checkOption($this->getLocator()->save_for_later_use);
    }

    /**
     * @throws Exception
     */
    public function chooseCardFromSavedCardsList() : void
    {
        $this->preparedClick($this->getLocator()->use_saved_card);
        //it takes time for one-click form to load and to allow selecting cards
        $this->waitUntil(80, [$this, 'waitUntilOptionSelected'], [$this->getLocator()->cc_token, $this->grabTextFrom($this->getLocator()->cc_token_text)]);
        $this->preparedClick($this->getLocator()->use_card);
        //make sure that credit card form is loaded again and we're ready to proceed
        $this->switchToCreditCardUIFrame();
        $this->waitForText($this->getLocator()->use_different_card);
        //sometimes we need to fill cvv
        try {
            $this->fillField($this->getLocator()->cvv, $this->getPaymentMethod()->getCvv());
        } catch (TimeOutException $e) {
        }
        $this->switchToIFrame();
    }
}
