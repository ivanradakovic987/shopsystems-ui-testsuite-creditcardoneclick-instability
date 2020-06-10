<?php

namespace Step\Acceptance\ShopSystem;

use Codeception\Scenario;
use Exception;
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
    private $guestCustomer;

    /**
     * @var CustomerConfig;
     */
    private $registeredCustomer;

    /**
     * @var CustomerConfig
     */
    private $adminUser;

    private $mappedPaymentActions;

    /**
     * @var array
     */
    private $redirectPaymentMethods = ['PayPal', 'iDEAL','AlipayCrossBorder', 'Sofort'];

    /**
     * GenericStep constructor.
     * @param Scenario $scenario
     * @param $gateway
     * @param $guestFileName
     * @param $registeredFileName
     * @param $adminFileName
     */
    public function __construct(Scenario $scenario, $gateway, $guestFileName, $registeredFileName, $adminFileName)
    {
        parent::__construct($scenario, $gateway);
        $this->setLocator($this->getDataFromDataFile($this->getFullPath(
            FileSytem::SHOP_SYSTEM_LOCATOR_FOLDER_PATH .
            static::STEP_NAME . DIRECTORY_SEPARATOR .
            static::STEP_NAME . 'Locators.json'
        )));
        /** @var TYPE_NAME $this */
        $this->mappedPaymentActions = $this->getDataFromDataFile(
            $this->getFullPath(FileSytem::MAPPED_PAYMENT_ACTIONS_FOLDER_PATH
                . static::STEP_NAME . DIRECTORY_SEPARATOR . static::STEP_NAME . 'MappedPaymentActions.json')
        );
        $this->createCustomerObjects($guestFileName, $registeredFileName, $adminFileName);
    }

    /**
     * @param $guestFileName
     * @param $registeredFileName
     * @param $adminFileName
     */
    public function createCustomerObjects($guestFileName, $registeredFileName, $adminFileName): void
    {
        $dataFolderPath = $this->getFullPath(FileSytem::CUSTOMER_DATA_FOLDER_PATH);
        $this->guestCustomer = new CustomerConfig($this->getDataFromDataFile($dataFolderPath . $guestFileName));
        $this->registeredCustomer = new CustomerConfig($this->getDataFromDataFile(
            $dataFolderPath . $registeredFileName
        ));
        $this->adminUser = new CustomerConfig($this->getDataFromDataFile($dataFolderPath . $adminFileName));
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
     * @param $name
     * @param $value
     */
    public function putValueInDatabase($name, $value): void
    {
        if (!$this->existsInDatabase($name)) {
            $this->haveInDatabase(
                static::SETTINGS_TABLE_NAME,
                [static::NAME_COLUMN_NAME => $name,
                    static::VALUE_COLUMN_NAME => $value]
            );
        } else {
            $this->updateInDatabase(
                static::SETTINGS_TABLE_NAME,
                [static::VALUE_COLUMN_NAME => $value],
                [static::NAME_COLUMN_NAME => $name]
            );
        }
    }

    /**
     * @param String $name
     * @return mixed
     */
    public function existsInDatabase($name)
    {
        return $this->grabFromDatabase(
            static::SETTINGS_TABLE_NAME,
            static::NAME_COLUMN_NAME,
            [static::NAME_COLUMN_NAME => $name]
        );
    }

    /**
     * @param String $minPurchaseSum
     * @throws Exception
     */
    public function fillBasket($minPurchaseSum): void
    {
        $this->amOnPage($this->getLocator()->page->product);

        $amount = intdiv((int)$minPurchaseSum, (int)$this->getLocator()->product->price) + 1;
        //add to basket goods to fulfill desired purchase amount
        $this->fillField($this->getLocator()->product->quantity, $amount);
        $this->preparedClick($this->getLocator()->product->add_to_cart, 80);
    }

    /**
     *
     * @param $customerType
     * @throws Exception
     */
    public function fillBillingDetails($customerType): void
    {
        $this->preparedFillField(
            $this->getLocator()->checkout->street_address,
            $this->getCustomer($customerType)->getStreetAddress()
        );
        $this->preparedFillField($this->getLocator()->checkout->town, $this->getCustomer($customerType)->getTown());
        $this->preparedFillField(
            $this->getLocator()->checkout->post_code,
            $this->getCustomer($customerType)->getPostCode()
        );
        $this->preparedFillField($this->getLocator()->checkout->phone, $this->getCustomer($customerType)->getPhone());
    }

    /**
     * @param $customerType
     * @return mixed
     */
    public function getCustomer($customerType)
    {
        if ($customerType === static::REGISTERED_CUSTOMER) {
            return $this->registeredCustomer;
        } elseif ($customerType === static::ADMIN_USER) {
            return $this->adminUser;
        }
        return $this->guestCustomer;
    }

    /**
     * @return mixed
     */
    public function goToCheckout(): void
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
        if (strcasecmp($paymentMethod, static::GUARANTEED_INVOICE) === 0 ||
            strcasecmp($paymentMethod, static::SOFORTBANKING) === 0) {
            $paymentMethod = $this->getActingPaymentMethod($paymentMethod);
        }
        $this->waitUntil(80, [$this, 'checkPaymentActionInTransactionTable'], [$paymentMethod, $paymentAction]);
        $this->assertEquals($this->checkPaymentActionInTransactionTable([$paymentMethod, $paymentAction]), true);
    }

    /**
     * @param array $paymentArgs
     * @return bool
     */
    public function checkPaymentActionInTransactionTable($paymentArgs): bool
    {
        $transactionTypes = $this->getColumnFromDatabaseNoCriteria(
            static::TRANSACTION_TABLE_NAME,
            static::TRANSACTION_TYPE_COLUMN_NAME
        );
        $tempTxType = $this->selectTxTypeFromMappedPaymentActions($paymentArgs);
        return end($transactionTypes) === $tempTxType;
    }

    /**
     * @param array $paymentArgs
     * @return mixed
     */
    public function selectTxTypeFromMappedPaymentActions($paymentArgs)
    {
        $txnType = $paymentArgs[1];
        return $this->getMappedTxTableValuesForPaymentMethod($paymentArgs[0])->$txnType;
    }

    /**
     * @param string $paymentMethod
     * @return mixed
     */
    public function getMappedTxTableValuesForPaymentMethod($paymentMethod)
    {
        return $this->getMappedPaymentActions()->$paymentMethod->tx_table;
    }

    /**
     * @return mixed
     */
    public function getMappedPaymentActions()
    {
        return $this->mappedPaymentActions;
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
     * @return array
     */
    public function getRedirectPaymentMethods(): array
    {
        return $this->redirectPaymentMethods;
    }

    /**
     * @return bool
     */
    public function isCustomerRegistered(): bool
    {
        $guest = $this->grabFromDatabase(
            static::CUSTOMER_TABLE,
            static::CUSTOMER_EMAIL_COLUMN_NAME,
            [static::CUSTOMER_EMAIL_COLUMN_NAME => $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress()]
        );
        return $guest === $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress();
    }

    /**
     * @param $paymentMethod
     * @return string
     */
    public function getActingPaymentMethod($paymentMethod): string
    {
        if (strcasecmp($paymentMethod, static::CREDIT_CARD_ONE_CLICK) === 0) {
            return 'CreditCard';
        }
        if (strcasecmp($paymentMethod, static::GUARANTEED_INVOICE) === 0) {
            return 'Invoice';
        }
        if (strcasecmp($paymentMethod, static::SOFORTBANKING) === 0) {
            return 'Sofortbanking';
        }
        return $paymentMethod;
    }
}
