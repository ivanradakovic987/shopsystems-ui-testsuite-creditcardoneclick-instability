<?php


namespace Step\Acceptance;


use Codeception\Scenario;
use Helper\Config\Customer\CustomerConfig;
use Helper\Config\PaymentMethod\CreditCardConfig;
use Helper\Config\PaymentMethod\PayPalConfig;

class GenericShopSystemStep extends GenericStep
{
    public const SETTINGS_TABLE_NAME = '';
    public const NAME_COLUMN_NAME = '';
    public const VALUE_COLUMN_NAME = '';
    public const TRANSACTION_TABLE_NAME = '';
    public const WIRECARD_OPTION_NAME = '';
    public const CURRENCY_OPTION_NAME = '';
    public const DEFAULT_COUNTRY_OPTION_NAME = '';

    /**
     * @var CustomerConfig;
     */
    private $customer;


    /**
     * GenericStep constructor.
     * @param Scenario $scenario
     * @param string $type
     */
    public function __construct(Scenario $scenario)
    {
        parent::__construct($scenario);
        $this->setLocator($this->getDataFromDataFile(SHOP_SYSTEM_LOCATOR_FOLDER_PATH . static::STEP_NAME . DIRECTORY_SEPARATOR . static::STEP_NAME . 'Locators.json'));
    }

    /**
     * @param $type
     * @param $dataFileName
     */
    public function setConfigObject($type, $dataFileName): void
    {
        //check if full path provided in config file
        $dataFolderPath = '';
        if (pathinfo($dataFileName)['dirname'] === '.') {
            $dataFolderPath = CUSTOMER_DATA_FOLDER_PATH;
        }
        $this->customer = new CustomerConfig($this->getDataFromDataFile($dataFolderPath . $dataFileName));
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param $name
     * @param $value
     */
    public function putValueInDatabase($name, $value): void
    {
        if (!$this->grabFromDatabase(static::SETTINGS_TABLE_NAME, static::NAME_COLUMN_NAME, [static::NAME_COLUMN_NAME => $name])) {
            $this->haveInDatabase(static::SETTINGS_TABLE_NAME, [static::NAME_COLUMN_NAME => $name,
                static::VALUE_COLUMN_NAME => $value]);
        } else {
            $this->updateInDatabase(static::SETTINGS_TABLE_NAME,
                [static::VALUE_COLUMN_NAME => $value],
                [static::NAME_COLUMN_NAME => $name]
            );
        }
    }

    /**
     * @param $purchaseSum
     */
    public function fillBasket($purchaseSum): void
    {
        $this->amOnPage($this->getLocator()->page->product);

        $amount = intdiv((int)$purchaseSum, (int)$this->getLocator()->product->price);
        //add to basket goods to fulfill desired purchase amount
        $this->fillField($this->getLocator()->product->quantity, $amount);
        $this->click($this->getLocator()->product->add_to_cart);
    }

    /**
     * @return mixed
     */
    public function goToCheckout()
    {
        $this->amOnPage($this->getLocator()->page->checkout);
    }

    /**
     * @param $currency
     * @param $defaultCountry
     */
    public function configureShopSystemCurrencyAndCountry($currency, $defaultCountry): void
    {
        $this->putValueInDatabase(static::CURRENCY_OPTION_NAME, $currency);
        $this->putValueInDatabase(static::DEFAULT_COUNTRY_OPTION_NAME, $defaultCountry);
    }
}