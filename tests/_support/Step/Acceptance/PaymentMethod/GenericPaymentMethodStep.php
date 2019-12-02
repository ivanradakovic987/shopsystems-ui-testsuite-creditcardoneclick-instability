<?php


namespace Step\Acceptance\PaymentMethod;


use Codeception\Scenario;
use Helper\Config\GenericConfig;
use Helper\Config\PaymentMethod\CreditCardConfig;
use Helper\Config\PaymentMethod\PayPalConfig;
use Step\Acceptance\GenericStep;

class GenericPaymentMethodStep extends GenericStep
{
    /**
     * @var CreditCardConfig|PayPalConfig;
     */
    private $paymentMethod;

    /**
     * GenericStep constructor.
     * @param Scenario $scenario
     */
    public function __construct(Scenario $scenario)
    {
        parent::__construct($scenario);
        $this->setLocator($this->getDataFromDataFile(PAYMENT_METHOD_LOCATOR_FOLDER_PATH . static::STEP_NAME . DIRECTORY_SEPARATOR . static::STEP_NAME . 'Locators.json'));
    }

    /**
     * @param $type
     * @param $dataFileName
     */
    public function setConfigObject($type, $dataFileName): void
    {
        $configObjectMap = [
            CREDIT_CARD => CreditCardConfig::class,
            PAY_PAL => PayPalConfig::class
        ];
        //check if full path provided in config file
        $dataFolderPath = '';
        if (pathinfo($dataFileName)['dirname'] === '.') {
            $dataFolderPath = PAYMENT_METHOD_DATA_FOLDER_PATH;
        }
        $this->paymentMethod = new $configObjectMap[$type]($this->getDataFromDataFile($dataFolderPath . $dataFileName));
    }


    /**
     * @return GenericConfig| CreditCardConfig| PayPalConfig
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
}