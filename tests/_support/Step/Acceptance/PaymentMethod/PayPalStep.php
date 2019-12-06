<?php

namespace Step\Acceptance\PaymentMethod;

use Facebook\WebDriver\Exception\TimeOutException;
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
     * @return mixed
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop()
    {
        $this->performPaypalLogin();
        $this->preparedClick($this->getLocator()->continue, 80);
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

    // @TODO: what's with the empty method?
    // we need to define this method for consistency, because it will be called in every scenario, empty method just means do nothing here
    public function performPaymentActionsInTheShop()
    {
    }

}