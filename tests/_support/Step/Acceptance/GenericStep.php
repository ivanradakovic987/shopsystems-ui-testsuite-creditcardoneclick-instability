<?php


namespace Step\Acceptance;


use Exception;

use Helper\Config\Customer\CustomerConfig;
use Helper\Config\PaymentMethod\CreditCardConfig;
use Helper\Config\PaymentMethod\PayPalConfig;


/**
 * Class GenericActor
 * @package Helper\Actor
 */
class GenericStep extends \AcceptanceTester
{
    /**
     * @var string
     */
    private $stepName = '';

    /**
     * @var
     */
    private $gateway;

    /**
     * @var
     */
    private $locator;

    /**
     * @var CustomerConfig;
     */
    private $customer;

    /**
     * @var CreditCardConfig;
     */
    private $creditCard;


    /**
     * @return CreditCardConfig
     */
    public function getCreditCard(): CreditCardConfig
    {
        return $this->creditCard;
    }


    /**
     * @param mixed $locator
     */
    public function setLocator($locator): void
    {
        $this->locator = $locator;
    }

    /**
     * @return string
     */
    public function getStepName(): string
    {
        return $this->stepName;
    }

    /**
     * @param string $stepName
     */
    public function setStepName(string $stepName): void
    {
        $this->stepName = $stepName;
    }

    /**
     * @return mixed
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @param mixed $gateway
     */
    public function setGateway($gateway): void
    {
        $this->gateway = $gateway;
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->customer;
    }


    /**
     * @param $type
     * @param $dataFileName
     */
    public function setConfigObject($type, $dataFileName): void
    {
        $configObjectMap = [
            CUSTOMER => CustomerConfig::class,
            CREDIT_CARD => CreditCardConfig::class,
            PAY_PAL => PayPalConfig::class
        ];
        //check if full path provided in config file
        $dataFolderPath = '';
        if (pathinfo($dataFileName)['dirname'] === '.') {
            $dataFolderPath = PAYMENT_METHOD_DATA_FOLDER_PATH;
            if ($type === CUSTOMER) {
                $dataFolderPath = CUSTOMER_DATA_FOLDER_PATH;
            }
        }
        $objectData = $this->getDataFromDataFile($dataFolderPath . $dataFileName);
        $this->$type = new $configObjectMap[$type]($objectData);
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
     * @param $pageKeyWord
     * @param int $maxTimeout
     */
    public function waitUntilPageLoaded($pageKeyWord, $maxTimeout = 60): void
    {
        $counter = 0;
        while ($counter <= $maxTimeout) {
            $this->wait(1);
            $counter++;
            $currentUrl = $this->grabFromCurrentUrl();
            if ($currentUrl !== '' && $pageKeyWord !== '' && strpos($currentUrl, $pageKeyWord) !== false) {
                break;
            }
        }
    }
}