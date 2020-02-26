<?php

namespace Step\Acceptance\PaymentMethod;

use Facebook\WebDriver\Exception\TimeOutException;
use Step\Acceptance\iPerformPayment;
use Exception;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;

/**
 * Class PayPalStep
 * @package Step\Acceptance\PaymentMethod
 */
class PayPalStep extends GenericPaymentMethodStep implements iPerformPayment
{
    const STEP_NAME = 'PayPal';

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop() : void
    {
        $this->performPaypalLogin();

        try {
            $this->preparedClick($this->getLocator()->continue, 80);
        } catch (ElementClickInterceptedException $e) {
            //sometimes we need to accept cookies first
            $this->waitForText($this->getLocator()->payment_page_text, 60);
            $this->preparedClick($this->getLocator()->accept_cookies, 80);
            $this->preparedClick($this->getLocator()->continue, 80);
        }
        $this->wait(1);
        $this->preparedClick($this->getLocator()->pay_now, 60);
    }

    /**
     * Method performPaypalLogin
     * @throws Exception
     */
    public function performPaypalLogin()
    {
        $this->preparedFillField($this->getLocator()->email, $this->getPaymentMethod()->getUserName());
        //sometimes we can enter password in the same page with username and sometimes we have to click "Next"
        try {
            $this->waitForElementVisible($this->getLocator()->password);
        } catch (TimeOutException $e) {
            $this->preparedClick($this->getLocator()->next);
        }
        $this->preparedFillField($this->getLocator()->password, $this->getPaymentMethod()->getPassword());
        $this->preparedClick($this->getLocator()->login);
    }

    // we need to define this method for consistency, because it will be called in every scenario, empty method just means do nothing here
    public function fillFieldsInTheShop()
    {
    }
}
