<?php

namespace Step\Acceptance\PaymentMethod;

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
        //check if current url contains Alipay
        $this->waitUntil(60, [$this, 'waitUntilRedirectedPageLoaded'], [$this->getLocator()->alipay_url]);
    }
}
