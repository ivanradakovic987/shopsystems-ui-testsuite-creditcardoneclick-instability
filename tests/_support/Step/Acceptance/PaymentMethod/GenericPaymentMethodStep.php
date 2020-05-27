<?php

namespace Step\Acceptance\PaymentMethod;

use Codeception\Scenario;
use Helper\Config\GenericConfig;
use Helper\Config\PaymentMethod\CreditCardConfig;
use Helper\Config\PaymentMethod\PayPalConfig;
use Step\Acceptance\GenericStep;
use Helper\Config\FileSytem;

/**
 * Class GenericPaymentMethodStep
 * @package Step\Acceptance\PaymentMethod
 */
class GenericPaymentMethodStep extends GenericStep
{
    /**
     * @var CreditCardConfig|PayPalConfig;
     */
    private $paymentMethod;

    /**
     * @var array
     */
    private $configObjectMap = [
        self::CREDIT_CARD => CreditCardConfig::class,
        self::CREDIT_CARD_ONE_CLICK => CreditCardConfig::class,
        self::PAY_PAL => PayPalConfig::class,
        self::IDEAL => PayPalConfig::class
    ];

    /**
     * GenericStep constructor.
     * @param Scenario $scenario
     * @param String $gateway
     * @param String $type
     * @param String $paymentMethodDataFileName
     */
    public function __construct(Scenario $scenario, $gateway, $type, $paymentMethodDataFileName)
    {
        parent::__construct($scenario, $gateway);
        $this->setLocator($this->getDataFromDataFile($this->getFullPath(FileSytem::PAYMENT_METHOD_LOCATOR_FOLDER_PATH)
            . static::STEP_NAME . DIRECTORY_SEPARATOR . static::STEP_NAME . 'Locators.json'));
        $this->createPaymentMethodObject($type, $paymentMethodDataFileName);
    }

    /**
     * @param String $type
     * @param String $dataFileName
     */
    public function createPaymentMethodObject($type, $dataFileName): void
    {
        $dataFolderPath = $this->getFullPath(FileSytem::PAYMENT_METHOD_DATA_FOLDER_PATH);
        $this->paymentMethod = new $this->configObjectMap[$type](
            $this->getDataFromDataFile($dataFolderPath . $dataFileName));
    }

    /**
     * @return GenericConfig| CreditCardConfig| PayPalConfig
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
}
