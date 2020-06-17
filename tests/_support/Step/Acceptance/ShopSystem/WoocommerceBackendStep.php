<?php

namespace Step\Acceptance\ShopSystem;

use Exception;

/**
 * Class WoocommerceBackendStep
 * Contains backend functions that are not called directly from feature file
 * @package Step\Acceptance|ShopSystem
 */
class WoocommerceBackendStep extends GenericShopSystemStep
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

    const CUSTOMER_TABLE = 'wp_users';

    const CUSTOMER_EMAIL_COLUMN_NAME = 'user_email';

    const CUSTOMER_PASSWORD_COLUMN_NAME = 'user_pass';

    const CUSTOMER_LOGIN_COLUMN_NAME = 'user_login';

    const CUSTOMER_DATE_COLUMN_NAME = 'user_registered';

    const CUSTOMER_META_TABLE = 'wp_usermeta';

    const CUSTOMER_META_USER_ID_COLUMN_NAME = 'user_id';

    const CUSTOMER_META_KEY_COLUMN_NAME = 'meta_key';

    const CUSTOMER_META_VALUE_COLUMN_NAME = 'meta_value';

    const CUSTOMER_META_KEY_BILLING_ADDRESS_VALUE = 'billing_address_1';

    const CUSTOMER_META_KEY_SHIPPING_ADDRESS_VALUE = 'shipping_address_1';

    const CUSTOMER_META_KEY_BILLING_COUNTRY_VALUE = 'billing_country';

    const CUSTOMER_META_KEY_SHIPPING_COUNTRY_VALUE = 'shipping_country';

    const CREDIT_CARD_ONE_CLICK_CONFIGURATION_VALUE = 'cc_vault_enabled';

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

    const PAYMENT_ACTION_FIELD_NAME = 'payment_action';

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
            return;
        }
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

    /**
     * @param $elName
     * @param $elValue
     * @param $elLocator
     * @param $pageLocator
     * @param $paymentAction
     * @throws Exception
     */
    public function selectOptionBasedOnElementName($elName, $elValue, $elLocator, $pageLocator, $paymentAction)
    {
        //payment action should be taken from parameter
        if ($elName === static::PAYMENT_ACTION_FIELD_NAME) {
            $this->preparedSelectOption(
                $this->getLocator()->$pageLocator->$elLocator,
                ucfirst(strtolower($paymentAction))
            );
            return;
        }
        $this->preparedSelectOption($this->getLocator()->$pageLocator->$elLocator, $elValue);
    }

    /**
     * Method doesn't fail the test if checkbox is not checked
     * @param $elementLocator
     * @param $pageLocator
     * @throws Exception
     */
    public function checkOptionIfNotAlreadyChecked($elementLocator, $pageLocator)
    {
        if (!$this->isCheckboxChecked($this->getLocator()->$pageLocator->$elementLocator)) {
            $this->preparedCheckOption($this->getLocator()->$pageLocator->$elementLocator);
        }
    }

    /**
     * @param $elName
     * @param $elValue
     * @param $elLocator
     * @param $pageLocator
     * @param $paymentAction
     */
    public function seeInFieldBasedOnElementName($elName, $elValue, $elLocator, $pageLocator, $paymentAction)
    {
        //payment action should be taken from parameter
        if ($elName === static::PAYMENT_ACTION_FIELD_NAME) {
            $this->seeInField(
                $this->getLocator()->$pageLocator->$elLocator,
                ucfirst(strtolower($paymentAction))
            );
            return;
        }
        $this->seeInField($this->getLocator()->$pageLocator->$elLocator, $elValue);
    }
}
