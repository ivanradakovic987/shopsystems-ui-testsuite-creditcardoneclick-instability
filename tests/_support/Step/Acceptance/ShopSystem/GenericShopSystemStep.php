<?php

namespace Step\Acceptance\ShopSystem;

use Codeception\Scenario;
use Helper\Config\Customer\CustomerConfig;
use Helper\Config\FileSytem;
use Step\Acceptance\GenericStep;

/**
 * Class GenericShopSystemStep
 * @package Step\Acceptance|ShopSystem
 */
class GenericShopSystemStep extends GenericStep
{
    /**
     * @var CustomerConfig;
     */
    private $customer;

    /**
     * @var array
     */
    private $mappedPaymentActions = [
        'CreditCard' => [
            'config' => [
                'row' => 'payment_action'
            ],
            'tx_table' => [
                'authorization' => 'authorization',
                'purchase' => 'purchase'
            ]
        ],
        'PayPal' => [
            'config' => [
                'row' => 'payment_action'
            ],
            'tx_table' => [
                'authorization' => 'authorization',
                'purchase' => 'debit'
            ]
        ]
    ];

    /**
     * @var array
     */
    private $redirectPaymentMethods = ['PayPal'];

    /**
     * GenericStep constructor.
     * @param Scenario $scenario
     * @param $gateway
     * @param $customerDataFileName
     */
    public function __construct(Scenario $scenario, $gateway, $customerDataFileName)
    {
        parent::__construct($scenario, $gateway);
        $this->setLocator($this->getDataFromDataFile($this->getFullPath(FileSytem::SHOP_SYSTEM_LOCATOR_FOLDER_PATH . static::STEP_NAME . DIRECTORY_SEPARATOR . static::STEP_NAME . 'Locators.json')));
        $this->createCustomerObject($customerDataFileName);
    }

    /**
     * @param $dataFileName
     */
    public function createCustomerObject($dataFileName): void
    {
        $dataFolderPath = $this->getFullPath(FileSytem::CUSTOMER_DATA_FOLDER_PATH);
        $this->customer = new CustomerConfig($this->getDataFromDataFile($dataFolderPath . $dataFileName));
    }

    /**
     * @param $name
     * @param $value
     */
    public function putValueInDatabase($name, $value): void
    {
        if (!$this->existsInDatabase($name)) {
            $this->haveInDatabase(static::SETTINGS_TABLE_NAME,
                [static::NAME_COLUMN_NAME => $name,
                    static::VALUE_COLUMN_NAME => $value]);
        } else {
            $this->updateInDatabase(static::SETTINGS_TABLE_NAME,
                [static::VALUE_COLUMN_NAME => $value],
                [static::NAME_COLUMN_NAME => $name]
            );
        }
    }

    /**
     * @param String $currency
     * @param String $defaultCountry
     */
    public function configureShopSystemCurrencyAndCountry($currency, $defaultCountry): void
    {
        $this->putValueInDatabase(static::CURRENCY_OPTION_NAME, $currency);
        $this->putValueInDatabase(static::DEFAULT_COUNTRY_OPTION_NAME, $defaultCountry);
    }

    /**
     * @param String $minPurchaseSum
     */
    public function fillBasket($minPurchaseSum): void
    {
        $this->amOnPage($this->getLocator()->page->product);

        $amount = intdiv((int)$minPurchaseSum, (int)$this->getLocator()->product->price) + 1;
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
     *
     */
    public function validateSuccessPage(): void
    {
        $this->waitUntil(60, [$this, 'waitUntilPageLoaded'], [$this->getLocator()->page->order_received]);
        $this->see($this->getLocator()->order_received->order_confirmed_message);
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     */
    public function validateTransactionInDatabase($paymentMethod, $paymentAction): void
    {
        $this->waitUntil(80, [$this, 'checkPaymentActionInTransactionTable'], [$paymentMethod, $paymentAction]);
        $this->assertEquals($this->checkPaymentActionInTransactionTable([$paymentMethod, $paymentAction]), true);
    }

    /**
     * @param array $paymentArgs
     * @return bool
     */
    public function checkPaymentActionInTransactionTable($paymentArgs): bool
    {
        $transactionTypes = $this->getColumnFromDatabaseNoCriteria(static::TRANSACTION_TABLE_NAME, 'transaction_type');
        $tempTxType = $this->selectTxTypeFromMappedPaymentActions($paymentArgs);
        return end($transactionTypes) === $tempTxType;
    }

    /**
     * @return array
     */
    public function getMappedPaymentActions(): array
    {
        return $this->mappedPaymentActions;
    }

    /**
     * @return array
     */
    public function getRedirectPaymentMethods(): array
    {
        return $this->redirectPaymentMethods;
    }

    /**
     * @param String $name
     * @return mixed
     */
    public function existsInDatabase($name)
    {
        return $this->grabFromDatabase(static::SETTINGS_TABLE_NAME, static::NAME_COLUMN_NAME, [static::NAME_COLUMN_NAME => $name]);
    }

    /**
     * @param String $paymentMethod
     * @return bool
     */
    public function isRedirectPaymentMethod($paymentMethod): bool
    {
        return in_array($paymentMethod, $this->getRedirectPaymentMethods(), false);
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param array $paymentMethod
     * @return mixed
     */
    public function getMappedTxTableValuesForPaymentMethod($paymentMethod)
    {
       return $this->getMappedPaymentActions()[$paymentMethod]['tx_table'];
    }


    /**
     * @param array $paymentArgs
     * @return mixed
     */
    public function selectTxTypeFromMappedPaymentActions($paymentArgs)
    {
        return $this->getMappedTxTableValuesForPaymentMethod($paymentArgs[0])[$paymentArgs[1]];
    }
}