<?php

namespace Step\Acceptance\ShopSystem;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Step\Acceptance\iConfigurePaymentMethod;
use Step\Acceptance\iPrepareCheckout;
use Step\Acceptance\iValidateSuccess;
use Exception;

/**
 * Class WoocommerceStep
 * @package Step\Acceptance|ShopSystem
 */
class WoocommerceStep extends WoocommerceAdministrationStep implements
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
}
