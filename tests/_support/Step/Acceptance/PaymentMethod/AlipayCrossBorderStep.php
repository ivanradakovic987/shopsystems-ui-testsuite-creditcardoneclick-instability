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
class AlipayCrossBorderStep extends GenericPaymentMethodStep implements iPerformPayment
{
    const STEP_NAME = 'AlipayCrossBorder';

    /**
     * @throws Exception
     */
    public function performPaymentMethodActionsOutsideShop(): void
    {
//        $this->wait(5);
//        $currentUrl = $this->executeJS("return location.href");
//        $this->assertContains($this->getLocator()->alipay_url, $currentUrl, 'Check if we are redirected to Alipay.');

        //check if current url contains Alipay
        $this->waitUntil(60, [$this, 'waitUntilRedirectedPageLoaded'], [$this->getLocator()->alipay_url]);
    }
}
