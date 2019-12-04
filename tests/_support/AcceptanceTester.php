<?php

use Codeception\Actor;
use Helper\Config\Filesystem;
use Step\Acceptance\PaymentMethod\CreditCardStep;
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
// @TODO: Correct order of members and methods:
// @TODO: 1. const, 2. private members, 3. public methods, 4. private methods
// @TODO: most important public method (e.g. init) should be first in order and then depending on the context and importance followed by the other public ones)
// @TODO: remove empty doc blocks - we don't need them - methods should have docblock with @param (add Type before varname here), @return and @throws(if existing)
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     *
     */
    // @TODO: Default visibility of Constants is public - if we don't want to use private once then we don't have to add the visibility
    // @TODO: if we use shopInstanceMap as private const then this is correct because then we would need the be specific about the visibility
    public const CREDIT_CARD = 'creditCard';

    /**
     *
     */
    public const PAY_PAL = 'payPal';

    /**
     *
     */
    // @TODO: can be const - should not be adaptable during execution?
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
    // @TODO: Not used? - so we can remove it
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
    // @TODO: probably we do not need the getter for this - we can use the member directly
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
    // @TODO: this should be private - only used in class specific context
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

        // @TODO: Do we ned an OR here? Or can we check only for supported shopsystem?
        // @TODO: The exception message reports both cases anyway - look at suggested solution - we could avoid the else too
        // @TODO: Or we have specific error messages - if is set and if is not supported
        $usedShopEnvVariable = getenv('SHOP_SYSTEM');
        if ($usedShopEnvVariable || !$this->isShopSystemSupported($usedShopEnvVariable)) {
            $this->shopInstance = new $this->shopInstanceMap[$usedShopEnvVariable]($this->getScenario(), $this->gateway);
            //tell which customer data to use and initialize customer config
            // @TODO: setConfigObject - this method does not do what it tells us - setConfigObject creates a new customerconfig object
            // @TODO: but let's do that in the next step as soon as we have updated this file ;)
            $this->getShopInstance()->setConfigObject($this->configData->customer_data);
            $this->getShopInstance()->configureShopSystemCurrencyAndCountry($this->configData->currency, $this->configData->default_country);
        } else {
            throw new \RuntimeException('Environment variable SHOP_SYSTEM is not set or requested shop system is not supported');
        }
    }

    private function createShopSystemInstance($shopSystemName): GenericShopSystemStep
    {
        // Hint: Use guard clause for immediate exit
        if (!$this->isShopSystemSupported($shopSystemName)) {
            throw new \RuntimeException('Environment variable SHOP_SYSTEM is not set or requested shop system is not supported');
        }
        $usedShopEnvVariable = getenv('SHOP_SYSTEM');

        /** @var GenericShopSystemStep $shopInstance */
        $shopInstance = new $this->shopInstanceMap[$usedShopEnvVariable]($this->getScenario(), $this->gateway);
        $shopInstance->setConfigObject($this->configData->customer_data);
        $shopInstance->configureShopSystemCurrencyAndCountry($this->configData->currency, $this->configData->default_country);

        return $shopInstance;
    }

    /**
     * @param $paymentMethod
     */
    // @TODO: This method does not only select the payment method but also creates the object - but we can think about this in another step
    private function selectPaymentMethod($paymentMethod): void
    {
        $this->paymentMethod = new $this->paymentMethodInstanceMap[$paymentMethod]($this->getScenario(), $this->getGateway());

        //tell which payment method data to use and initialize customer config
        //@TODO: is there a way to make the paymentmethod name consistent over the whole project to avoid that strtolower and lcfirst is needed?
        // in locators.json we use payment method names as prefix, like creditcard_data
        $paymentMethodDataName = strtolower($paymentMethod . '_data');
        //all php variables are camel case
        // @TODO: we don't need to use getPaymentMethod when we have access to the member directly
        // @TODO: - getters are mostly for public usage (so, if we want to use it within another class/context)
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
