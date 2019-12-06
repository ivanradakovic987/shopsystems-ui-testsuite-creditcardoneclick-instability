<?php

use Codeception\Actor;
use Helper\Config\Filesystem;
use Step\Acceptance\PaymentMethod\CreditCardStep;
use Step\Acceptance\PaymentMethod\GenericPaymentMethodStep;
use Step\Acceptance\ShopSystem\GenericShopSystemStep;
use Step\Acceptance\ShopSystem\PrestashopStep;
use Step\Acceptance\ShopSystem\WoocommerceStep;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */

/**
 * Class AcceptanceTester
 */
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    const CREDIT_CARD = 'creditCard';

    const PAY_PAL = 'payPal';

    // @TODO: rethink the whole map structure
    //this is used to generate new class instance, so const doesn't work here
    private $shopInstanceMap = [
        'prestashop' => Step\Acceptance\ShopSystem\PrestashopStep::class,
        'woocommerce' => Step\Acceptance\ShopSystem\WoocommerceStep::class
    ];

    private $paymentMethodInstanceMap = [
        'CreditCard' => Step\Acceptance\PaymentMethod\CreditCardStep::class,
        'PayPal' => Step\Acceptance\PaymentMethod\PayPalStep::class
    ];

    /**
     * @var Actor|PrestashopStep|WoocommerceStep
     */
    private $shopInstance;

    /**
     * @var
     */
    private $gateway;

    /**
     * @var Actor|CreditCardStep|
     */
    private $paymentMethod;

    /**
     * @var
     */
    private $configData;

    /**
     * @Given I initialize shop system
     * @throws Exception
     */
    public function iInitializeShopSystem(): void
    {
        $usedShopEnvVariable = getenv('SHOP_SYSTEM');
        if (!$usedShopEnvVariable) {
            throw new \RuntimeException('Environment variable SHOP_SYSTEM is not set');
        }
        $this->configData = $this->getDataFromDataFile($this->getFullPath(Filesystem::CONFIG_FILE));
        $this->gateway = $this->configData->gateway;
        $this->shopInstance = $this->createShopSystemInstance($usedShopEnvVariable);
    }

    /**
     * @Given I activate :paymentMethod payment action :paymentAction in configuration
     * @param $paymentMethod
     * @param $paymentAction
     * @throws Exception
     */
    public function iActivatePaymentActionInConfiguration($paymentMethod, $paymentAction): void
    {
        $this->shopInstance->configurePaymentMethodCredentials($paymentMethod, $paymentAction);
    }

    /**
     * @Given I prepare checkout with purchase sum :minPurchaseSum in shop system
     * @param $minPurchaseSum
     * @throws Exception
     */
    public function iPrepareCheckoutWithPurchaseSumInShopSystem($minPurchaseSum): void
    {
        $this->shopInstance->fillBasket($minPurchaseSum);
        $this->shopInstance->goToCheckout();
        $this->shopInstance->fillCustomerDetails();
    }

    /**
     * @Then I see :text
     * @param $text
     */
    public function iSee($text): void
    {
        $this->see($text);
    }

    /**
     * @Then I start :paymentMethod payment
     * @param $paymentMethod
     * @throws Exception
     */
    public function iStartPayment($paymentMethod): void
    {
        $this->shopInstance->startPayment($paymentMethod);
    }

    /**
     * @Given I perform :paymentMethod payment actions in the shop
     * @param $paymentMethod
     * @throws Exception
     */
    public function iPerformPaymentActionsInTheShop($paymentMethod): void
    {
        $this->paymentMethod = $this->createPaymentMethod($paymentMethod);
        $this->paymentMethod->performPaymentActionsInTheShop();
        $this->shopInstance->proceedWithPayment($paymentMethod);
    }

    /**
     * @When I perform payment method actions  outside the shop
     * @throws Exception
     */
    public function iPerformPaymentMethodActionsOutsideTheShop(): void
    {
        $this->paymentMethod->performPaymentMethodActionsOutsideShop();
    }

    /**
     * @Then I see successful payment
     */
    public function iSeeSuccessfulPayment(): void
    {
        $this->shopInstance->validateSuccessPage();
    }

    /**
     * @Then I see :paymentMethod transaction type :paymentAction in transaction table
     * @param $paymentMethod
     * @param $paymentAction
     */
    public function iSeeTransactionTypeInTransactionTable($paymentMethod, $paymentAction): void
    {
        $this->shopInstance->validateTransactionInDatabase($paymentMethod, $paymentAction);
    }


    /**
     * @param $paymentMethod
     * @return GenericPaymentMethodStep
     */
    private function createPaymentMethod($paymentMethod): GenericPaymentMethodStep
    {
        //tell which payment method data to use and initialize customer config
        //@TODO: rethink how to make it consistent not to need  strtolower and lcfirst
        // in locators.json we use payment method names as prefix, like creditcard_data
        $paymentMethodDataName = strtolower($paymentMethod . '_data');
        //all php variables are camel case
        $paymentMethodInstance = new $this->paymentMethodInstanceMap[$paymentMethod](
            $this->getScenario(),
            $this->gateway,
            //all php variables are camel case
            lcfirst($paymentMethod), $this->configData->$paymentMethodDataName);

        return $paymentMethodInstance;
    }

    /**
     * @param $shopSystemName
     * @return GenericShopSystemStep
     */
    private function createShopSystemInstance($shopSystemName): GenericShopSystemStep
    {
        // Hint: Use guard clause for immediate exit
        if (!$this->isShopSystemSupported($shopSystemName)) {
            throw new \RuntimeException('Environment variable SHOP_SYSTEM is not set or requested shop system is not supported');
        }
        /** @var GenericShopSystemStep $shopInstance */
        $shopInstance = new $this->shopInstanceMap[$shopSystemName]($this->getScenario(), $this->gateway, $this->configData->customer_data);
        $shopInstance->configureShopSystemCurrencyAndCountry($this->configData->currency, $this->configData->default_country);

        return $shopInstance;
    }

    /**
     * @param $shopSystemName
     * @return bool
     */
    private function isShopSystemSupported($shopSystemName): bool
    {
        return array_key_exists($shopSystemName, $this->shopInstanceMap);
    }
}
