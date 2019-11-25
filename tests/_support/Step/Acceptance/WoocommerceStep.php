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
     * @param array $mappedPaymentActions
     */
    public function setMappedPaymentActions($mappedPaymentActions)
    {
        $this->mappedPaymentActions = $mappedPaymentActions;
    }


    /**
     * @param $paymentMethod
     * @param $paymentAction
     * @return string
     */
    private function buildPaymentMethodConfig($paymentMethod, $paymentAction): string
    {
        $array = [];
        $gatewayConfiguration = PAYMENT_METHOD_CONFIG_FOLDER_PATH . $paymentMethod . 'Config.json';

        $gatewayConfigurationRow = $this->getMappedPaymentActions()[$paymentMethod]['config']['row'];

        if (file_exists($gatewayConfiguration)) {
            $jsonData = $this->getDataFromDataFile($gatewayConfiguration);
            $gateway = $this->getGateway();
            if (!empty($jsonData) && !empty($jsonData->$gateway)) {
                $array = get_object_vars($jsonData->$gateway);
                foreach (array_keys($array) as $key) {
                    if ($key === $gatewayConfigurationRow) {
                        $array[$key] = $paymentAction;
                    }
                }
            }
        }
        return serialize($array);
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     * @return mixed|void
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
    {
        try {
            $this->haveInDatabase('wp_options',
                ['option_value' => $this->buildPaymentMethodConfig($paymentMethod, $paymentAction)],
                ['option_name' => 'woocommerce_wirecard_ee_' . strtolower($paymentMethod) . '_settings']);
        } catch (Exception $e) {
            $this->updateInDatabase(
                'wp_options',
                ['option_value' => $this->buildPaymentMethodConfig($paymentMethod, $paymentAction)],
                ['option_name' => 'woocommerce_wirecard_ee_' . strtolower($paymentMethod) . '_settings']
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
     *
     */
    public function validateTransactionInDatabase()
    {
        // TODO: Implement validateTransactionInDatabase() method.
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