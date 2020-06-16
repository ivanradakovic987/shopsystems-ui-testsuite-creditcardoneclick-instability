<?php

namespace Step\Acceptance\ShopSystem;

use Exception;

/**
 * Class WoocommerceBackendStep
 * Contains backend functions that are not called directly from feature file
 * @package Step\Acceptance|ShopSystem
 */
class WoocommerceBackendStep
{
    const CREDIT_CARD_ONE_CLICK = 'creditCardOneClick';

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

    public $wooInstance;

    public function __construct($shopInstance)
    {
        $this->wooInstance=$shopInstance;
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
            $this->wooInstance->putValueInDatabase($optionName, $optionValue);
        }
    }

    /**
     * @param String $paymentMethod
     * @throws Exception
     */
    public function startCreditCardPayment($paymentMethod)
    {
        $paymentMethodForm = strtolower($paymentMethod) . '_form';
        $this->wooInstance->waitForElementVisible($this->wooInstance->getLocator()->checkout->$paymentMethodForm);
        $this->wooInstance->scrollTo($this->wooInstance->getLocator()->checkout->$paymentMethodForm);
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
        if (!$this->wooInstance->grabFromDatabase(
            static::SHIPPING_ZONES_TABLE_NAME,
            static::SHIPPING_ZONES_COLUMN_NAME,
            [static::SHIPPING_ZONES_COLUMN_NAME => $zoneName]
        )) {
            $zoneId = $this->wooInstance->haveInDatabase(
                static::SHIPPING_ZONES_TABLE_NAME,
                [static::SHIPPING_ZONES_COLUMN_NAME => $zoneName,
                    static::SHIPPING_ZONES_ORDER_COLUMN_NAME => 0]
            );
            $this->wooInstance->haveInDatabase(
                static::SHIPPING_ZONE_METHODS_TABLE_NAME,
                [static::SHIPPING_ZONE_ID_COLUMN_NAME => $zoneId,
                    static::SHIPPING_ZONE_METHODS_METHOD_ID_COLUMN_NAME => $shippingMethods,
                    static::SHIPPING_ZONE_METHODS_ORDER_COLUMN_NAME => 1,
                    static::SHIPPING_ZONE_METHODS_ENABLED_COLUMN_NAME => 1]
            );
            $this->wooInstance->haveInDatabase(
                static::SHIPPING_ZONE_LOCATIONS_TABLE_NAME,
                [static::SHIPPING_ZONE_ID_COLUMN_NAME => $zoneId,
                    static::SHIPPING_ZONE_LOCATIONS_CODE_COLUMN_NAME => $zoneRegions,
                    static::SHIPPING_ZONE_LOCATIONS_TYPE_COLUMN_NAME => $locationType]
            );
            return;
        }
        $zoneId = $this->wooInstance->grabFromDatabase(
            static::SHIPPING_ZONES_TABLE_NAME,
            static::SHIPPING_ZONE_ID_COLUMN_NAME,
            [static::SHIPPING_ZONES_COLUMN_NAME => $zoneName]
        );
        $this->wooInstance->updateInDatabase(
            static::SHIPPING_ZONE_METHODS_TABLE_NAME,
            [static::SHIPPING_ZONE_METHODS_METHOD_ID_COLUMN_NAME => $shippingMethods],
            [static::SHIPPING_ZONE_ID_COLUMN_NAME => $zoneId]
        );
        $this->wooInstance->updateInDatabase(
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
            $this->wooInstance->preparedSelectOption(
                $this->getLocator()->$pageLocator->$elLocator,
                ucfirst(strtolower($paymentAction))
            );
            return;
        }
        $this->wooInstance->preparedSelectOption($this->getLocator()->$pageLocator->$elLocator, $elValue);
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
