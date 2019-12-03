<?php


use Codeception\Actor;
use Helper\Config\Filesystem;
use Step\Acceptance\PaymentMethod\CreditCardStep;
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

    /**
     *
     */
    public const CREDIT_CARD = 'creditCard';

    /**
     *
     */
    public const PAY_PAL = 'payPal';

    /**
     *
     */
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
     * @return mixed
     */
    public function getConfigData()
    {
        return $this->configData;
    }

    /**
     * @return mixed
     */
    public function getGateway()
    {
        return $this->gateway;
    }


    /**
     * @return Actor|PrestashopStep|WoocommerceStep
     */
    private function getShopInstance()
    {
        return $this->shopInstance;
    }

    /**
     * @return Actor|CreditCardStep|
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param $paymentMethod
     * @return bool
     */
    public function isPaymentMethodSelected($paymentMethod): bool
    {
        return $this->paymentMethod === null;
    }

    /**
     * @param $shopSystemName
     * @return bool
     */
    public function isShopSystemSupported($shopSystemName): bool
    {
        return array_key_exists($shopSystemName, $this->shopInstanceMap);
    }

    /**
     * @Given I initialize shop system
     * @throws Exception
     */
    public function iInitializeShopSystem(): void
    {
        $this->configData = $this->getDataFromDataFile($this->getFullPath(Filesystem::CONFIG_FILE));
        $this->gateway = $this->configData->gateway;
        $usedShopEnvVariable = getenv('SHOP_SYSTEM');
        if ($usedShopEnvVariable || !$this->isShopSystemSupported($usedShopEnvVariable)) {
            $this->shopInstance = new $this->shopInstanceMap[$usedShopEnvVariable]($this->getScenario(), $this->gateway);
            //tell which customer data to use and initialize customer config
            $this->getShopInstance()->setConfigObject($this->configData->customer_data);
            $this->getShopInstance()->configureShopSystemCurrencyAndCountry($this->configData->currency, $this->configData->default_country);
        } else {
            throw new \RuntimeException('Environment variable SHOP_SYSTEM is not set or requested shop system is not supported');
        }
    }

    /**
     * @param $paymentMethod
     */
    private function selectPaymentMethod($paymentMethod): void
    {
        $this->paymentMethod = new $this->paymentMethodInstanceMap[$paymentMethod]($this->getScenario(), $this->getGateway());

        //tell which payment method data to use and initialize customer config
        //@TODO: is there a way to make the paymentmethod name consistent over the whole project to avoid that strtolower and lcfirst is needed?
        // in locators.json we use payment method names as prefix, like creditcard_data
        $paymentMethodDataName = strtolower($paymentMethod . '_data');
        //all php variables are camel case
        $this->getPaymentMethod()->setConfigObject(lcfirst($paymentMethod), $this->configData->$paymentMethodDataName);
    }

    /**
     * @Given I activate :paymentMethod payment action :paymentAction in configuration
     * @param $paymentMethod
     * @param $paymentAction
     * @throws Exception
     */
    public function iActivatePaymentActionInConfiguration($paymentMethod, $paymentAction): void
    {
        $this->getShopInstance()->configurePaymentMethodCredentials($paymentMethod, $paymentAction);
    }

    /**
     * @Given I prepare checkout with purchase sum :minPurchaseSum in shop system
     * @param $minPurchaseSum
     * @throws Exception
     */
    public function iPrepareCheckoutWithPurchaseSumInShopSystem($minPurchaseSum): void
    {
        $this->getShopInstance()->fillBasket($minPurchaseSum);
        $this->getShopInstance()->goToCheckout();
        $this->getShopInstance()->fillCustomerDetails();
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
        $this->getShopInstance()->startPayment($paymentMethod);
    }


    /**
     * @Given I perform :paymentMethod payment actions in the shop
     * @param $paymentMethod
     * @throws Exception
     */
    public function iPerformPaymentActionsInTheShop($paymentMethod): void
    {
        $this->selectPaymentMethod($paymentMethod);
        $this->getPaymentMethod()->performPaymentActionsInTheShop();
        $this->getShopInstance()->proceedWithPayment($paymentMethod);
    }

    /**
     * @When I perform payment method actions  outside the shop
     * @throws Exception
     */
    public function iPerformPaymentMethodActionsOutsideTheShop(): void
    {
        $this->getPaymentMethod()->performPaymentMethodActionsOutsideShop();
    }

    /**
     * @Then I see successful payment
     */
    public function iSeeSuccessfulPayment(): void
    {
        $this->getShopInstance()->validateSuccessPage();
    }

    /**
     * @Then I see :paymentMethod transaction type :paymentAction in transaction table
     * @param $paymentMethod
     * @param $paymentAction
     */
    public function iSeeTransactionTypeInTransactionTable($paymentMethod, $paymentAction): void
    {
        $this->getShopInstance()->validateTransactionInDatabase($paymentMethod, $paymentAction);
    }


}
