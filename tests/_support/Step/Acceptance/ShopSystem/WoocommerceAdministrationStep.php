<?php

namespace Step\Acceptance\ShopSystem;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;

/**
 * Class WoocommerceAdministrationStep
 * Contains methods from test-cases for configuring payment methods in Wordpress administration panel
 * @package Step\Acceptance\ShopSystem
 */
class WoocommerceAdministrationStep extends WoocommerceBackendStep
{
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