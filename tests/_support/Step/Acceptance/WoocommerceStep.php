<?php

namespace Step\Acceptance;

/**
 * Class WoocommerceActor
 * @package Helper\Actor
 */

use Codeception\Module\Db as Db;
use Codeception\Scenario;
use Exception;

/**
 * Class WoocommerceStep
 * @package Step\Acceptance
 */
class WoocommerceStep extends GenericStep implements iConfigurePaymentMethod, iPrepareCheckout, iValidateSuccess
{
    public const SETTINGS_TABLE_NAME = 'wp_options';
    public const OPTION_NAME = 'option_name';
    public const OPTION_VALUE = 'option_value';
    public const TRANSACTION_TABLE_NAME = 'wp_wirecard_payment_gateway_tx';
    public const WIRECARD_OPTION_NAME = 'woocommerce_wirecard_ee_';

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
     * WoocommerceStep constructor.
     * @param Scenario $scenario
     */
    public function __construct(Scenario $scenario)
    {
        parent::__construct($scenario);
        $this->setStepName('Woocommerce');
        $this->setLocator($this->getDataFromDataFile(SHOP_SYSTEM_LOCATOR_FOLDER_PATH . $this->getStepName() . DIRECTORY_SEPARATOR . $this->getStepName() . 'Locators.json'));
    }

    /**
     * @return array
     */
    public function getMappedPaymentActions(): array
    {
        return $this->mappedPaymentActions;
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     * @return string
     */
    private function buildPaymentMethodConfig($paymentMethod, $paymentAction): string
    {
        $array = [];
        $gatewayConfigurationFile = PAYMENT_METHOD_CONFIG_FOLDER_PATH . $paymentMethod . 'Config.json';

        $paymentActionConfigurationRow = $this->getMappedPaymentActions()[$paymentMethod]['config']['row'];
        $gateway = $this->getGateway();
        //process data in payment configuration file
        $jsonData = $this->getDataFromDataFile($gatewayConfigurationFile);
        if ($this->paymentMethodGatewayConfigExists($jsonData, $gateway)) {
            //convert json object to array
            $array = get_object_vars($jsonData->$gateway);
            //go through array and substitute payment action
            $array = $this->substituteArrayKey($array, $paymentActionConfigurationRow, $paymentAction);
        }
        return serialize($array);
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     * @return mixed|void
     * @throws Exception
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
    {

        $optionName = self::WIRECARD_OPTION_NAME . strtolower($paymentMethod) . '_settings';
        $optionValue = $this->buildPaymentMethodConfig($paymentMethod, $paymentAction);

        if (!$this->grabFromDatabase(self::SETTINGS_TABLE_NAME, self::OPTION_NAME, [self::OPTION_NAME => $optionName])) {
            $this->haveInDatabase(self::SETTINGS_TABLE_NAME, [self::OPTION_NAME => $optionName,
                self::OPTION_VALUE => $optionValue]);
        } else {
            $this->updateInDatabase(self::SETTINGS_TABLE_NAME,
                [self::OPTION_VALUE => $this->buildPaymentMethodConfig($paymentMethod, $paymentAction)],
                [self::OPTION_NAME => $optionName]
            );
        }
    }


    /**
     *
     */
    public function validateSuccessPage()
    {
        $this->waitUntilPageLoaded($this->getLocator()->page->order_received);
        $this->see($this->getLocator()->order_received->order_confirmed_message);
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     */
    public function validateTransactionInDatabase($paymentMethod, $paymentAction)
    {
        $this->seeInDatabase(
            self::TRANSACTION_TABLE_NAME,
            ['transaction_type' => $this->mappedPaymentActions[$paymentMethod]['tx_table'][$paymentAction]]
        );
        //check that last transaction in the table is the one under test
        $transactionTypes = $this->getColumnFromDatabaseNoCriteria(self::TRANSACTION_TABLE_NAME, 'transaction_type');
        $this->assertEquals(end($transactionTypes), $this->mappedPaymentActions[$paymentMethod]['tx_table'][$paymentAction]);

    }

    //add needed items to the basket

    /**
     * @param $purchaseSum
     * @return mixed
     */
    public function fillBasket($purchaseSum)
    {
        $this->amOnPage($this->getLocator()->page->product);

        $clickAmount = intdiv((int)$purchaseSum, (int)$this->getLocator()->product->price);
        //add to basket goods to fulfill desired purchase amount
        for ($i = 0; $i < $clickAmount; $i++) {
            $this->click($this->getLocator()->product->add_to_cart);
        }

    }

    //go to checkout

    /**
     * @return mixed
     */
    public function goToCheckout()
    {
        $this->amOnPage($this->getLocator()->page->checkout);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function fillCustomerDetails()
    {
        $this->preparedFillField($this->getLocator()->checkout->first_name, $this->getCustomer()->getFirstName());
        $this->preparedFillField($this->getLocator()->checkout->last_name, $this->getCustomer()->getLastName());
        $this->preparedClick($this->getLocator()->checkout->country);
        $this->preparedFillField($this->getLocator()->checkout->country_entry, $this->getCustomer()->getCountry());
        $this->preparedClick($this->getLocator()->checkout->country_entry_selected);
        $this->preparedFillField($this->getLocator()->checkout->street_address, $this->getCustomer()->getStreetAddress());
        $this->preparedFillField($this->getLocator()->checkout->town, $this->getCustomer()->getTown());
        $this->preparedFillField($this->getLocator()->checkout->post_code, $this->getCustomer()->getPostCode());
        $this->preparedFillField($this->getLocator()->checkout->phone, $this->getCustomer()->getPhone());
        $this->preparedFillField($this->getLocator()->checkout->email_address, $this->getCustomer()->getEmailAddress());
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function startPayment()
    {
        $this->wait(2);
        $this->preparedClick($this->getLocator()->checkout->place_order);
        $this->waitForElementVisible($this->getLocator()->checkout->credit_card_form);
        $this->scrollTo($this->getLocator()->checkout->credit_card_form);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function proceedWithPayment()
    {
        $this->preparedClick($this->getLocator()->order_pay->pay);
    }
}