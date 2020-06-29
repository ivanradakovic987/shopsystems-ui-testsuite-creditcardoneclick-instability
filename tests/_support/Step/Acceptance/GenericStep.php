<?php

namespace Step\Acceptance;

use AcceptanceTester;
use Codeception\Scenario;
use Exception;
use Facebook\WebDriver\Exception\NoAlertOpenException;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Class GenericStep
 * @package Step\Acceptance
 */
class GenericStep extends AcceptanceTester
{
    private $gateway;

    private $locator;

    /**
     * GenericStep constructor.
     * @param Scenario $scenario
     * @param $gateway
     */
    public function __construct(Scenario $scenario, $gateway)
    {
        parent::__construct($scenario);
        $this->gateway = $gateway;
    }

    /**
     * @param $element
     * @param int $timeout
     * @param $value
     * @throws Exception
     */
    public function preparedFillField($element, $value, $timeout = 30): void
    {
        $this->waitForElementVisible($element, $timeout);
        $this->fillField($element, $value);
    }

    /**
     * @param $element
     * @param int $timeout
     * @throws Exception
     */
    public function preparedClick($element, $timeout = 30): void
    {
        $this->waitForElementClickable($element, $timeout);
        $this->click($element);
    }

    /**
     * @param $element
     * @param $option
     * @param int $timeout
     * @throws Exception
     */
    public function preparedSelectOption($element, $option, $timeout = 30): void
    {
        $this->waitForElementClickable($element, $timeout);
        $this->selectOption($element, $option);
    }

    /**
     * @param $element
     * @param int $timeout
     * @throws Exception
     */
    public function preparedSeeElement($element, $timeout = 30): void
    {
        $this->waitForElementVisible($element, $timeout);
        $this->seeElement($element);
    }

    /**
     * @param int $maxTimeout
     * @param array|null $function
     * @param array|null $functionArgs
     */
    public function waitUntil($maxTimeout = 80, array $function = null, array $functionArgs = null): void
    {
        $counter = 0;
        while ($counter <= $maxTimeout) {
            $this->wait(1);
            $counter++;
            if ($function !== null) {
                if (call_user_func($function, $functionArgs)) {
                    break;
                }
            }
        }
    }

    /**
     * Function checks if page is loaded by searching for $pageKeyWord in current url
     * @param $pageKeyWord
     * @return bool
     */
    public function waitUntilPageLoaded($pageKeyWord): bool
    {
        $currentUrl = $this->executeJS("return location.href");
        if (empty($currentUrl) || empty($pageKeyWord)) {
                return false;
        }
        if (strpos($currentUrl, $pageKeyWord[0]) !== false) {
            $this->wait(3);
            return true;
        }
        return false;
    }

    /**
     * @param array $selectorDetails
     * @return bool
     */
    public function waitUntilOptionSelected($selectorDetails): bool
    {
        try {
            $this->seeOptionIsSelected($selectorDetails[0], $selectorDetails[1]);
            return true;
        } catch (AssertionFailedError $e) {
            $this->selectOption($selectorDetails[0], $selectorDetails[1]);
            return false;
        }
    }

    /**
     * @param $message
     * @return bool
     * @throws \Codeception\Exception\ModuleException
     */
    public function waitUntilSeeInPopupWindow($message): bool
    {
        try {
            $this->seeInPopup($message[0]);
            return true;
        } catch (NoAlertOpenException $e) {
            return false;
        }
    }

    /**
     * @param mixed $locator
     */
    public function setLocator($locator): void
    {
        $this->locator = $locator;
    }

    /**
     * @return mixed
     */
    // we need this in child classes when configuring shops
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @return mixed
     */
    public function getLocator()
    {
        return $this->locator;
    }

    /**
     * @param $element
     * @param int $timeout
     * @throws Exception
     */
    public function preparedCheckOption($element, $timeout = 30): void
    {
        $this->waitForElementClickable($element, $timeout);
        $this->checkOption($element);
    }

    /**
     * Returns true if checkBox is check and doesn't fail the test if it is not checked
     * @param $locator
     * @return bool
     */
    public function isCheckboxChecked($locator): bool
    {
        try {
            $this->seeCheckboxIsChecked($locator);
            return true;
        } catch (AssertionFailedError $e) {
            return false;
        }
    }

    public function waitUntilIframeLoaded($locator): bool
    {
        $wirecardFrame = $this->executeJS(
            'return document.querySelector("#' . $locator[0] . '")'
        );

        if ($wirecardFrame == null) {
            return false;
        }
        return true;
    }
}
