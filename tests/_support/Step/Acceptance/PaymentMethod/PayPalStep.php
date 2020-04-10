<?php

namespace Step\Acceptance\PaymentMethod;

use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Step\Acceptance\iPerformPayment;
use Exception;

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
            $this->preparedClick($this->getLocator()->pay_now_start, 60);
        } catch (NoSuchElementException $e) {
            $this->tryLongPayPalCheckoutProcess();
            $this->wait(1);
            $this->preparedClick($this->getLocator()->pay_now, 60);
        }
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

    /**
     * @return mixed|void
     */
    public function fillFieldsInTheShop()
    {
    }

    /**
     * @throws Exception
     */
    public function tryLongPayPalCheckoutProcess()
    {
        try {
            $this->preparedClick($this->getLocator()->continue, 80);
        } catch (WebDriverException $e) {
            //sometimes we need to accept cookies first
            $this->waitForText($this->getLocator()->payment_page_text, 60);
            $this->preparedClick($this->getLocator()->accept_cookies, 80);
            $this->preparedClick($this->getLocator()->continue, 80);
        }
    }
}
