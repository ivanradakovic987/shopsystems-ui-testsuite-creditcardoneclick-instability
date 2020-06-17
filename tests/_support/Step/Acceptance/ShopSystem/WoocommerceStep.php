<?php

namespace Step\Acceptance\ShopSystem;

use Codeception\Scenario;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Step\Acceptance\iConfigurePaymentMethod;
use Step\Acceptance\iPrepareCheckout;
use Step\Acceptance\iValidateSuccess;
use Exception;

/**
 * Class WoocommerceStep
 * @package Step\Acceptance|ShopSystem
 */
class WoocommerceStep extends WoocommerceBackendStep implements
    iConfigurePaymentMethod,
    iPrepareCheckout,
    iValidateSuccess
{
    /**
     * Keeps data from paymentMethod config json file
     * It is being set in goToConfigurationPageAndCheckIfEnteredDataIsShown
     * @var array
     */
    public $paymentMethodConfig = [];

    /**
     * Keeps transaction type value provided in feature file
     * It is being set in goToConfigurationPageAndCheckIfEnteredDataIsShown
     * @var string
     */
    public $txType = '';

    /**
     * @param String $paymentMethod
     * @param String  $paymentAction
     * @return mixed|void
     * @throws Exception
     */
    public function configurePaymentMethodCredentials($paymentMethod, $paymentAction)
    {
        $actingPaymentMethod = $this->getActingPaymentMethod($paymentMethod);
        $optionName = self::WIRECARD_OPTION_NAME . strtolower($actingPaymentMethod) . '_settings';
        $optionValue = serialize($this->buildPaymentMethodConfig(
            $actingPaymentMethod,
            $paymentAction,
            $this->getMappedPaymentActions(),
            $this->getGateway()
        ));

        $this->putValueInDatabase($optionName, $optionValue);
        $this->configurePaymentMethodCreditCardOneClick($paymentMethod, $optionName, $optionValue);
    }

    /**
     * Method registers new user into User table and adds Billing and Shipping country and address into UsersMeta table
     */
    public function registerCustomer()
    {
        if ($this->isCustomerRegistered() !== true) {
            $userId = $this->haveInDatabase(
                static::CUSTOMER_TABLE,
                [static::CUSTOMER_EMAIL_COLUMN_NAME => $this->getCustomer(
                    static::REGISTERED_CUSTOMER
                )->getEmailAddress(),
                    static::CUSTOMER_PASSWORD_COLUMN_NAME => md5($this->getCustomer(
                        static::REGISTERED_CUSTOMER
                    )->getPassword()),
                    static::CUSTOMER_LOGIN_COLUMN_NAME => $this->getCustomer(
                        static::REGISTERED_CUSTOMER
                    )->getLoginUserName(),
                    static::CUSTOMER_DATE_COLUMN_NAME => date('Y-m-d h:i:s')
                ]
            );
            $this->haveInDatabase(
                static::CUSTOMER_META_TABLE,
                [static::CUSTOMER_META_USER_ID_COLUMN_NAME => $userId,
                    static::CUSTOMER_META_KEY_COLUMN_NAME => self::CUSTOMER_META_KEY_BILLING_ADDRESS_VALUE,
                    static::CUSTOMER_META_VALUE_COLUMN_NAME => $this->getCustomer(
                        static::REGISTERED_CUSTOMER
                    )->getStreetAddress()
                ]
            );
            $this->haveInDatabase(
                static::CUSTOMER_META_TABLE,
                [static::CUSTOMER_META_USER_ID_COLUMN_NAME => $userId,
                    static::CUSTOMER_META_KEY_COLUMN_NAME => self::CUSTOMER_META_KEY_SHIPPING_ADDRESS_VALUE,
                    static::CUSTOMER_META_VALUE_COLUMN_NAME => $this->getCustomer(
                        static::REGISTERED_CUSTOMER
                    )->getStreetAddress()
                ]
            );
            $this->haveInDatabase(
                static::CUSTOMER_META_TABLE,
                [static::CUSTOMER_META_USER_ID_COLUMN_NAME => $userId,
                    static::CUSTOMER_META_KEY_COLUMN_NAME => self::CUSTOMER_META_KEY_BILLING_COUNTRY_VALUE,
                    static::CUSTOMER_META_VALUE_COLUMN_NAME => $this->getCustomer(
                        static::REGISTERED_CUSTOMER
                    )->getCountryId()
                ]
            );
            $this->haveInDatabase(
                static::CUSTOMER_META_TABLE,
                [static::CUSTOMER_META_USER_ID_COLUMN_NAME => $userId,
                    static::CUSTOMER_META_KEY_COLUMN_NAME => self::CUSTOMER_META_KEY_SHIPPING_COUNTRY_VALUE,
                    static::CUSTOMER_META_VALUE_COLUMN_NAME => $this->getCustomer(
                        static::REGISTERED_CUSTOMER
                    )->getCountryId()
                ]
            );
        }
    }

    /**
     * @param String $paymentMethod
     * @throws Exception
     */
    public function startPayment($paymentMethod): void
    {
        if (strcasecmp($paymentMethod, static::GUARANTEED_INVOICE) !== 0) {
            $paymentMethod = $this->getActingPaymentMethod($paymentMethod);
        }
        $this->wait(2);
        $paymentMethodRadioButtonLocator  = 'wirecard_' . strtolower($paymentMethod);
        $this->preparedClick($this->getLocator()->checkout->$paymentMethodRadioButtonLocator);
    }

    /**
     * @param String $paymentMethod
     * @throws Exception
     */
    public function proceedWithPayment($paymentMethod): void
    {
        if (!$this->isRedirectPaymentMethod($paymentMethod)) {
            $this->preparedClick($this->getLocator()->order_pay->pay);
        }
    }

    /**
     * @param $customerType
     * @throws Exception
     */
    public function fillCustomerDetails($customerType): void
    {
        //woocommerce is dynamically loading possible payment methods
        // while filling form, so we need to make sure all elements are fillable or clickable
        $this->preparedFillField(
            $this->getLocator()->checkout->first_name,
            $this->getCustomer($customerType)->getFirstName()
        );
        $this->preparedFillField(
            $this->getLocator()->checkout->last_name,
            $this->getCustomer($customerType)->getLastName()
        );
        $this->preparedClick(
            $this->getLocator()->checkout->country
        );
        $this->preparedFillField(
            $this->getLocator()->checkout->country_entry,
            $this->getCustomer($customerType)->getCountry()
        );
        $this->preparedClick($this->getLocator()->checkout->country_entry_selected);
        $this->fillBillingDetails($customerType);
        $this->preparedFillField(
            $this->getLocator()->checkout->email_address,
            $this->getCustomer($customerType)->getEmailAddress()
        );
    }

    /**
     * @throws Exception
     */
    public function logIn()
    {
        $this->amOnPage($this->getLocator()->page->sign_in);
        try {
            $this->preparedFillField(
                $this->getLocator()->sign_in->email,
                $this->getCustomer(static::REGISTERED_CUSTOMER)->getEmailAddress(),
                10
            );
            $this->preparedFillField(
                $this->getLocator()->sign_in->password,
                $this->getCustomer(static::REGISTERED_CUSTOMER)->getPassword()
            );
            $this->preparedClick($this->getLocator()->sign_in->sign_in, 60);
        } catch (NoSuchElementException $e) {
            $this->amOnPage($this->getLocator()->page->sign_in);
        }
    }

    /**
     * @param $paymentMethod
     * @throws Exception
     */
    public function placeTheOrder($paymentMethod)
    {
        $this->preparedClick($this->getLocator()->checkout->place_order);

        if (strcasecmp($paymentMethod, static::CREDIT_CARD) === 0) {
            $this->startCreditCardPayment($paymentMethod);
        }
    }

    /**
     * @throws Exception
     */
    public function logInToAdministrationPanel()
    {
        $this->amOnPage($this->getLocator()->page->admin_login);
        try {
            $this->preparedFillField(
                $this->getLocator()->wordpress_sign_in->user,
                $this->getCustomer(static::ADMIN_USER)->getEmailAddress(),
                10
            );
            $this->preparedFillField(
                $this->getLocator()->wordpress_sign_in->pass,
                $this->getCustomer(static::ADMIN_USER)->getPassword()
            );
            $this->preparedClick($this->getLocator()->wordpress_sign_in->login, 60);
        } catch (NoSuchElementException $e) {
            $this->amOnPage($this->getLocator()->page->admin_login);
        }
    }

    /**
     * @param $paymentMethod
     */
    public function deletePaymentMethodFromDb($paymentMethod)
    {
        $actingPaymentMethod = $this->getActingPaymentMethod($paymentMethod);
        $optionName = self::WIRECARD_OPTION_NAME . strtolower($actingPaymentMethod) . '_settings';

        $this->deleteFromDatabase(self::SETTINGS_TABLE_NAME, [self::NAME_COLUMN_NAME => $optionName]);
    }

    /**
     * Method checks that payment method is not enabled in payments page, enables it and goes to it's config page
     * @param $paymentMethod
     */
    public function activatePaymentMethod($paymentMethod)
    {
        $this->amOnPage($this->getLocator()->page->payments);

        $paymentMethodTab  = 'payments_tab_' . strtolower($paymentMethod);

        $this->preparedSeeElement($this->getLocator()->$paymentMethodTab->slider_disabled);
        $this->preparedClick($this->getLocator()->$paymentMethodTab->slider_disabled);
        $this->preparedSeeElement($this->getLocator()->$paymentMethodTab->slider_enabled);

        $this->preparedClick($this->getLocator()->$paymentMethodTab->set_up);

        $paymentMethodPage  = 'payments_' . strtolower($paymentMethod);
        $this->waitUntil(60, [$this, 'waitUntilPageLoaded'], [$this->getLocator()->page->$paymentMethodPage]);
    }

    /**
     * Method fills fields from payment method's configuration page using data from config json file
     * and checks all checkboxes if they are not already checked
     * Payment action field is the exception. It is getting filled with data from parameter.
     * Field's locator must have the same neme as in configuration file, with sufix that defines type of field
     * @param $paymentMethod
     * @param $paymentAction
     * @param $txType
     * @throws Exception
     */
    public function fillPaymentMethodFields($paymentMethod, $paymentAction, $txType)
    {
        $actingPaymentMethod = $this->getActingPaymentMethod($paymentMethod);
        // take data from payment method's configuration file
        $paymentMethodConfig = $this->buildPaymentMethodConfig(
            $actingPaymentMethod,
            $paymentAction,
            $this->getMappedPaymentActions(),
            $this->getGateway()
        );
        $this->paymentMethodConfig = $paymentMethodConfig;
        $this->txType = $txType;

        $pageLocator = strtolower($paymentMethod) . '_payment';
        foreach ($paymentMethodConfig as $name => $value) {
            // check the type of element based on name of locator
            if (array_key_exists($name . '_text', $this->getLocator()->$pageLocator)) {
                $locator = $name . '_text';
                $this->preparedFillField($this->getLocator()->$pageLocator->$locator, $value);
            } elseif (array_key_exists($name.'_select', $this->getLocator()->$pageLocator)) {
                $locator = $name . '_select';
                $this->selectOptionBasedOnElementName($name, $value, $locator, $pageLocator, $txType);
            } elseif (array_key_exists($name.'_check', $this->getLocator()->$pageLocator)) {
                $locator = $name . '_check';
                // All fields should be checked according to test-case
                $this->checkOptionIfNotAlreadyChecked($locator, $pageLocator);
            }
        }
        $this->preparedClick($this->getLocator()->$pageLocator->save_changes_button);
    }

    /**
     * @param $paymentMethod
     * @throws Exception
     */
    public function goToPaymentPageAndCheckIfPaymentMethodIsEnabled($paymentMethod)
    {
        $paymentMethodTab  = 'payments_tab_' . strtolower($paymentMethod);
        $this->amOnPage($this->getLocator()->page->payments);
        $this->preparedSeeElement($this->getLocator()->$paymentMethodTab->slider_enabled);
    }

    /**
     * Method compares values from fields in payment method's configuration page with data from config file
     * and checks if all checkboxes are checked
     * Payment action field is the exception. It's value is compared with data from parameter.
     * @param $paymentMethod
     */
    public function goToConfigurationPageAndCheckIfEnteredDataIsShown($paymentMethod)
    {
        $pageLocator  = 'payments_' . strtolower($paymentMethod);
        $this->amOnPage($this->getLocator()->page->$pageLocator);

        $pageLocator = strtolower($paymentMethod) . '_payment';
        foreach ($this->paymentMethodConfig as $name => $value) {
            // check the type of element based on name of locator
            if (array_key_exists($name . '_text', $this->getLocator()->$pageLocator)) {
                $locator = $name . '_text';
                $this->seeInField($this->getLocator()->$pageLocator->$locator, $value);
            } elseif (array_key_exists($name.'_select', $this->getLocator()->$pageLocator)) {
                $locator = $name . '_select';
                $this->seeInFieldBasedOnElementName($name, $value, $locator, $pageLocator, $this->txType);
            } elseif (array_key_exists($name.'_check', $this->getLocator()->$pageLocator)) {
                $locator = $name . '_check';
                // All fields should be checked according to test-case
                $this->seeCheckboxIsChecked($this->getLocator()->$pageLocator->$locator);
            }
        }
    }

    /**
     * Waits until popup window with successful test connection message is shown
     * @param $paymentMethod
     * @throws Exception
     */
    public function clickOnTestCredentialsAndCheckIfResultIsSuccessful($paymentMethod)
    {
        $pageLocator = strtolower($paymentMethod) . '_payment';
        $this->preparedClick($this->getLocator()->$pageLocator->test_credentials_button);
        $this->waitUntil(
            60,
            [$this, 'waitUntilSeeInPopupWindow'],
            [$this->getLocator()->merchant_configuration->successfully_tested]
        );
    }
}
