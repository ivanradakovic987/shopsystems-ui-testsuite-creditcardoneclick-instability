<?php


namespace Step\Acceptance;


use Codeception\Scenario;
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
     *
     */
    public const SETTINGS_TABLE_NAME = '';
    /**
     *
     */
    public const NAME_COLUMN_NAME = '';
    /**
     *
     */
    public const VALUE_COLUMN_NAME = '';
    /**
     *
     */
    public const TRANSACTION_TABLE_NAME = '';
    /**
     *
     */
    public const WIRECARD_OPTION_NAME = '';

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
     * @param mixed $locator
     */
    public function setLocator($locator): void
    {
        $this->locator = $locator;
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
     * @param $pageKeyWord
     * @return bool
     */
    public function waitUntilPageLoaded($pageKeyWord): bool
    {
        $currentUrl = $this->grabFromCurrentUrl();
        if ($currentUrl === '' && $pageKeyWord[0] === null) {
            return false;
        }
        if (strpos($currentUrl, $pageKeyWord[0]) !== false) {
            $this->wait(3);
            return true;
        }
        return false;
    }
}