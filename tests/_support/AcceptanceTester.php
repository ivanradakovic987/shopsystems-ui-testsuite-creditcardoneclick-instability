<?php

use Codeception\Actor;
use Helper\Config\FileSytem;
use Helper\Config\Environment;
use Step\Acceptance\PaymentMethod\CreditCardStep;
use Step\Acceptance\PaymentMethod\GenericPaymentMethodStep;
use Step\Acceptance\ShopSystem\GenericShopSystemStep;
use Step\Acceptance\ShopSystem\PrestashopStep;
use Step\Acceptance\ShopSystem\WoocommerceStep;
use Step\Acceptance\ShopSystem\Magento2Step;

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

    const CREDIT_CARD_ONE_CLICK = 'creditCardOneClick';

    const PAY_PAL = 'payPal';

    const IDEAL = 'iDEAL';

    const REGISTERED_CUSTOMER = 'registered customer';

    //this is used to generate new class instance, so const doesn't work here
    private $shopInstanceMap = [
        'prestashop' => Step\Acceptance\ShopSystem\PrestashopStep::class,
        'woocommerce' => Step\Acceptance\ShopSystem\WoocommerceStep::class,
        'magento2' => Step\Acceptance\ShopSystem\Magento2Step::class,
    ];

    private $paymentMethodInstanceMap = [
        'CreditCard' => Step\Acceptance\PaymentMethod\CreditCardStep::class,
        'CreditCardOneClick' => Step\Acceptance\PaymentMethod\CreditCardOneClickStep::class,
        'PayPal' => Step\Acceptance\PaymentMethod\PayPalStep::class,
        'iDEAL' => Step\Acceptance\PaymentMethod\IdealStep::class
    ];

    /**
     * @var Environment
     */
    public $env;

    /**
     * @var string
     */
    public $shopInstanceName;

    /**
     * @var Actor|PrestashopStep|WoocommerceStep|Magento2Step
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
        $this->env = new Environment();
        $this->shopInstanceName = $this->env->getEnv()['SHOP_SYSTEM'];
        $this->configData = $this->getDataFromDataFile($this->getFullPath(FileSytem::CONFIG_FILE));
        $this->gateway = $this->configData->gateway;
        if (!$this->shopInstance) {
            $this->shopInstance = $this->createShopSystemInstance($this->shopInstanceName);
        }
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
     * @Given I prepare checkout with purchase sum :minPurchaseSum in shop system as :arg2
     * @param $minPurchaseSum
     * @param $customerType
     * @throws Exception
     */
    public function iPrepareCheckoutWithPurchaseSumInShopSystemAs($minPurchaseSum, $customerType): void
    {
        if ($customerType === static::REGISTERED_CUSTOMER) {
            $this->shopInstance->logIn();
        }
        $this->shopInstance->fillBasket($minPurchaseSum);
        $this->shopInstance->goToCheckout();
        $this->shopInstance->fillCustomerDetails($customerType);
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
     * @Given I fill :paymentMethod fields in the shop
     * @param $paymentMethod
     * @throws Exception
     */
    public function iFillFieldsInTheShop($paymentMethod): void
    {
        $this->createPaymentMethodIfNeeded($paymentMethod);
        $this->paymentMethod->fillFieldsInTheShop();
        if (strcasecmp($paymentMethod, static::CREDIT_CARD_ONE_CLICK) !== 0) {
            $this->shopInstance->proceedWithPayment($paymentMethod);
        }
    }

    /**
     * @When I save :paymentMethod for later use
     * @param $paymentMethod
     * @throws Exception
     */
    public function iSaveForLaterUse($paymentMethod): void
    {
        $this->paymentMethod->saveForLaterUse($this->shopInstanceName);
        $this->shopInstance->proceedWithPayment($paymentMethod);
    }

    /**
     * @When I choose :paymentMethod from saved cards list
     * @param $paymentMethod
     * @throws Exception
     */
    public function iChooseFromSavedCardsList($paymentMethod): void
    {
        $this->paymentMethod->chooseCardFromSavedCardsList($this->shopInstanceName);
        $this->shopInstance->proceedWithPayment($paymentMethod);
    }

    /**
     * @Given I perform :paymentMethod actions outside of the shop
     * @param $paymentMethod
     * @throws Exception
     */
    public function iPerformActionsOutsideOfTheShop($paymentMethod): void
    {
        $this->createPaymentMethodIfNeeded($paymentMethod);
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
     * @return mixed
     */
    public function getEnv()
    {
        return $this->env->getEnv();
    }

    /**
     * @param $paymentMethod
     * @return GenericPaymentMethodStep
     */
    private function createPaymentMethod($paymentMethod): GenericPaymentMethodStep
    {
        //tell which payment method data to use and initialize customer config
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
        if (!$this->isShopSystemSupported($shopSystemName)) {
            throw new RuntimeException(
                'Environment variable SHOP_SYSTEM is not set or requested shop system is not supported'
            );
        }
        /** @var GenericShopSystemStep $shopInstance */
        $shopInstance = new $this->shopInstanceMap[$shopSystemName]($this->getScenario(),
                                                                    $this->gateway,
                                                                    $this->configData->guest_customer_data,
                                                                    $this->configData->registered_customer_data);
        $shopInstance->configureShopSystemCurrencyAndCountry(
            $this->configData->currency,
            $this->configData->default_country
        );
        $shopInstance->registerCustomer();
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

    /**
     * @param $paymentMethod
     * @return bool
     */
    private function paymentMethodCreated($paymentMethod): bool
    {
        if ($this->paymentMethod !== null) {
            return $this->paymentMethod::STEP_NAME === $paymentMethod;
        }
        return false;
    }

    /**
     * @param $paymentMethod
     */
    private function createPaymentMethodIfNeeded($paymentMethod): void
    {
        if (!$this->paymentMethodCreated($paymentMethod)) {
            $this->paymentMethod = $this->createPaymentMethod($paymentMethod);
        }
    }
}
