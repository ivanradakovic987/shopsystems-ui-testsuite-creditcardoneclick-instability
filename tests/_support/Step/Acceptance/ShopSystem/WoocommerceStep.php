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
class WoocommerceStep extends GenericShopSystemStep implements
    iConfigurePaymentMethod,
    iPrepareCheckout,
    iValidateSuccess
{
    const STEP_NAME = 'Woocommerce';

    const SETTINGS_TABLE_NAME = 'wp_options';

    const NAME_COLUMN_NAME = 'option_name';

    const VALUE_COLUMN_NAME = 'option_value';

    const TRANSACTION_TABLE_NAME = 'wp_wirecard_payment_gateway_tx';

    const TRANSACTION_TYPE_COLUMN_NAME = 'transaction_type';

    const WIRECARD_OPTION_NAME = 'woocommerce_wirecard_ee_';

    const CURRENCY_OPTION_NAME = 'woocommerce_currency';

    const DEFAULT_COUNTRY_OPTION_NAME = 'woocommerce_default_country';

    const CREDIT_CARD_ONE_CLICK_CONFIGURATION_VALUE = 'cc_vault_enabled';

    const CUSTOMER_TABLE = 'wp_users';

    const CUSTOMER_EMAIL_COLUMN_NAME = 'user_email';

    const CUSTOMER_PASSWORD_COLUMN_NAME = 'user_pass';

    const CUSTOMER_LOGIN_COLUMN_NAME = 'user_login';

    const CUSTOMER_DATE_COLUMN_NAME = 'user_registered';

    const SHIPPING_ZONES_TABLE_NAME = 'wp_woocommerce_shipping_zones';

    const SHIPPING_ZONES_COLUMN_NAME = 'zone_name';

    const SHIPPING_ZONE_ID_COLUMN_NAME = 'zone_id';

    const SHIPPING_ZONES_ORDER_COLUMN_NAME = 'zone_order';

    const SHIPPING_ZONE_METHODS_TABLE_NAME = 'wp_woocommerce_shipping_zone_methods';

    const SHIPPING_ZONE_METHODS_METHOD_ID_COLUMN_NAME = 'method_id';

    const SHIPPING_ZONE_METHODS_ORDER_COLUMN_NAME = 'method_order';

    const SHIPPING_ZONE_METHODS_ENABLED_COLUMN_NAME = 'is_enabled';

    const SHIPPING_ZONE_LOCATIONS_TABLE_NAME = 'wp_woocommerce_shipping_zone_locations';

    const SHIPPING_ZONE_LOCATIONS_CODE_COLUMN_NAME = 'location_code';

    const SHIPPING_ZONE_LOCATIONS_TYPE_COLUMN_NAME = 'location_type';

    const CUSTOMER_META_TABLE = 'wp_usermeta';

    const CUSTOMER_META_USER_ID_COLUMN_NAME = 'user_id';

    const CUSTOMER_META_KEY_COLUMN_NAME = 'meta_key';

    const CUSTOMER_META_VALUE_COLUMN_NAME = 'meta_value';

    const CUSTOMER_META_KEY_BILLING_ADDRESS_VALUE = 'billing_address_1';

    const CUSTOMER_META_KEY_SHIPPING_ADDRESS_VALUE = 'shipping_address_1';

    const CUSTOMER_META_KEY_BILLING_COUNTRY_VALUE = 'billing_country';

    const CUSTOMER_META_KEY_SHIPPING_COUNTRY_VALUE = 'shipping_country';

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
     * @param String $paymentMethod
     * @param String $optionName
     * @param $optionValue
     */
    public function configurePaymentMethodCreditCardOneClick($paymentMethod, $optionName, $optionValue)
    {
        if (strcasecmp($paymentMethod, static::CREDIT_CARD_ONE_CLICK) === 0) {
            $serializedValues = unserialize($optionValue);
            foreach (array_keys($serializedValues) as $key) {
                if ($key === self::CREDIT_CARD_ONE_CLICK_CONFIGURATION_VALUE) {
                    $serializedValues[$key] = 'yes';
                }
            }
            $optionValue = serialize($serializedValues);
            $this->putValueInDatabase($optionName, $optionValue);
        }
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
     * @param String $paymentMethod
     * @throws Exception
     */
    public function startCreditCardPayment($paymentMethod)
    {
        $paymentMethodForm = strtolower($paymentMethod) . '_form';
        $this->waitForElementVisible($this->getLocator()->checkout->$paymentMethodForm);
        $this->scrollTo($this->getLocator()->checkout->$paymentMethodForm);
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
     * @param $zoneName
     * @param $zoneRegions
     * @param $shippingMethods
     * @param $locationType
     */
    public function configureShippingZone($zoneName, $zoneRegions, $shippingMethods, $locationType)
    {
        $this->putShippingZoneInDatabase($zoneName, $zoneRegions, $shippingMethods, $locationType);
    }

    /**
     * @param $zoneName
     * @param $zoneRegions
     * @param $shippingMethods
     * @param $locationType
     */
    public function putShippingZoneInDatabase($zoneName, $zoneRegions, $shippingMethods, $locationType)
    {
        // check if zone already exists in database
        if (!$this->grabFromDatabase(
            static::SHIPPING_ZONES_TABLE_NAME,
            static::SHIPPING_ZONES_COLUMN_NAME,
            [static::SHIPPING_ZONES_COLUMN_NAME => $zoneName]
        )) {
            $zoneId = $this->haveInDatabase(
                static::SHIPPING_ZONES_TABLE_NAME,
                [static::SHIPPING_ZONES_COLUMN_NAME => $zoneName,
                    static::SHIPPING_ZONES_ORDER_COLUMN_NAME => 0]
            );
            $this->haveInDatabase(
                static::SHIPPING_ZONE_METHODS_TABLE_NAME,
                [static::SHIPPING_ZONE_ID_COLUMN_NAME => $zoneId,
                    static::SHIPPING_ZONE_METHODS_METHOD_ID_COLUMN_NAME => $shippingMethods,
                    static::SHIPPING_ZONE_METHODS_ORDER_COLUMN_NAME => 1,
                    static::SHIPPING_ZONE_METHODS_ENABLED_COLUMN_NAME => 1]
            );
            $this->haveInDatabase(
                static::SHIPPING_ZONE_LOCATIONS_TABLE_NAME,
                [static::SHIPPING_ZONE_ID_COLUMN_NAME => $zoneId,
                    static::SHIPPING_ZONE_LOCATIONS_CODE_COLUMN_NAME => $zoneRegions,
                    static::SHIPPING_ZONE_LOCATIONS_TYPE_COLUMN_NAME => $locationType]
            );
        } else {
            $zoneId = $this->grabFromDatabase(
                static::SHIPPING_ZONES_TABLE_NAME,
                static::SHIPPING_ZONE_ID_COLUMN_NAME,
                [static::SHIPPING_ZONES_COLUMN_NAME => $zoneName]
            );
            $this->updateInDatabase(
                static::SHIPPING_ZONE_METHODS_TABLE_NAME,
                [static::SHIPPING_ZONE_METHODS_METHOD_ID_COLUMN_NAME => $shippingMethods],
                [static::SHIPPING_ZONE_ID_COLUMN_NAME => $zoneId]
            );
            $this->updateInDatabase(
                static::SHIPPING_ZONE_LOCATIONS_TABLE_NAME,
                [static::SHIPPING_ZONE_LOCATIONS_CODE_COLUMN_NAME => $zoneRegions,
                    static::SHIPPING_ZONE_LOCATIONS_TYPE_COLUMN_NAME => $locationType],
                [static::SHIPPING_ZONE_ID_COLUMN_NAME => $zoneId]
            );
        }
    }
}
