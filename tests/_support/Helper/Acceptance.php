<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I
use phpDocumentor\Reflection\Types\Array_;
use Helper\Config\FileSytem;


class Acceptance extends \Codeception\Module
{

    /**
     * Method getDataFromDataFile
     * @param string $fileName
     * @return object
     */
    public static function getDataFromDataFile($fileName)
    {
        // decode the JSON feed
        $json_data = json_decode(file_get_contents($fileName));
        if (!$json_data) {
            $error = error_get_last();
            echo 'Failed to get data from ' . $fileName . '. Error was: ' . $error['message'];
        } else {
            return $json_data;
        }
    }

    /**
     * @param $array
     * @param $keyWord
     * @param $newValue
     * @return array
     */
    public static function substituteArrayKey($array, $keyWord, $newValue): array
    {
        foreach (array_keys($array) as $key) {
            if ($key === $keyWord) {
                $array[$key] = $newValue;
            }
        }
        return $array;
    }

    public static function paymentMethodGatewayConfigExists($fileData, $gateway): bool
    {
        return !empty($fileData) && !empty($fileData->$gateway);
    }

    /**
     * @param $paymentMethod
     * @param $paymentAction
     * @param $mappedPaymentActions
     * @param $gateway
     * @return array
     */
    public static function buildPaymentMethodConfig($paymentMethod, $paymentAction, $mappedPaymentActions, $gateway): array
    {
        $array = [];
        $gatewayConfigurationFile = self::getFullPath(FileSytem::PAYMENT_METHOD_CONFIG_FOLDER_PATH . $paymentMethod . 'Config.json');
        $paymentActionConfigurationRow = $mappedPaymentActions[$paymentMethod]['config']['row'];
        //process data in payment configuration file
        $jsonData = self::getDataFromDataFile($gatewayConfigurationFile);
        if (self::paymentMethodGatewayConfigExists($jsonData, $gateway)) {
            //convert json object to array
            $array = get_object_vars($jsonData->$gateway);
            //go through array and substitute payment action
            $array = self::substituteArrayKey($array, $paymentActionConfigurationRow, $paymentAction);
        }
        return $array;
    }

    /**
     * @param $path
     * @return string
     */
    public static function getFullPath($path): string
    {
        //check if path is full
        if (! realpath($path)) {
            return getcwd() . $path;
        }
        return $path;
    }
}
