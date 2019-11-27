<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I
define( 'CONFIG_FILE', 'config.json' );

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
        if (! $json_data) {
            $error = error_get_last();
            echo 'Failed to get data from '. $fileName .'. Error was: ' . $error['message'];
        } else {
            return $json_data;
        }
    }

    /**
     * @param $configType
     * @return mixed
     */
    public static  function getConfigFileFromConfig($configType)
    {
        return self::getDataFromDataFile('CONFIG_FILE')[$configType];
    }

    /**
     * @param $array
     * @param $keyWord
     * @param $newValue
     * @return array
     */
    public function substituteArrayKey($array, $keyWord, $newValue): array
    {
        foreach (array_keys($array) as $key) {
            if ($key === $keyWord) {
                $array[$key] = $newValue;
            }
        }
        return $array;
    }

    public function paymentMethodGatewayConfigExists($fileData, $gateway): bool
    {
        return !empty($fileData) && !empty($fileData->$gateway);
    }
}
