<?php


namespace Step\Acceptance\PaymentMethod;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;

class CreditCardOneClickStep extends CreditCardStep
{
    const STEP_NAME = 'CreditCard';

    /**
     * @param $shopSystem
     */
    public function saveForLaterUse($shopSystem): void
    {
        $this->checkOption($this->getSaveForLaterUseLocator($shopSystem));
    }

    /**
     * @throws Exception
     */
    public function chooseCardFromSavedCardsList($shopSystem) : void
    {
        $this->performChoosingCard($shopSystem);
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

    /**
     * @param $shopSystem
     * @throws Exception
     */
    public function performChoosingCard($shopSystem): void
    {
        switch ($shopSystem) {
            case 'magento2':
                $this->selectOption($this->getLocator()->cc_token_magento2, "ending");
                break;
            case 'woocommerce':
                $this->preparedClick($this->getLocator()->cc_token_woocommerce, 10);
                break;
            case 'prestashop':
                $this->preparedClick($this->getLocator()->use_saved_card);
                $this->waitUntil(
                    80,
                    [$this, 'waitUntilOptionSelected'],
                    [$this->getLocator()->cc_token_generic,
                        $this->grabTextFrom($this->getLocator()->cc_token_generic_text)]
                );
                $this->preparedClick($this->getLocator()->use_card);
                break;
        }
    }

    /**
     * @param $shopSystem
     * @return String
     */
    private function getSaveForLaterUseLocator($shopSystem): String
    {
        if (strpos($shopSystem, 'magento2') !== false) {
            return $this->getLocator()->save_for_later_use_magento2;
        }
        return $this->getLocator()->save_for_later_use;
    }
}
