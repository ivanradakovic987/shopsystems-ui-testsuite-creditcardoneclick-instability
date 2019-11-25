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
     * @return string
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

    public static  function getConfigFileFromConfig($configType)
    {
        return self::getDataFromDataFile('CONFIG_FILE')[$configType];
    }

    /**
     * @param string $paymentMethod
     * @param string $paymentAction
     * @return string
     * @since 2.0.3
     */
    public function buildConfig( $paymentAction, $paymentMethod )
    {
        if ( !defined( 'GATEWAY_CONFIG' ) ) define( 'GATEWAY_CONFIG', '/tests/_data/gateway_configs' );
        $gatewayConfiguration = getcwd() .  GATEWAY_CONFIG . DIRECTORY_SEPARATOR . $paymentMethod . '.json';

        $gateway = getenv( 'GATEWAY' );
        $gatewayConfigurationRow = $this->mappedPaymentActions[$paymentMethod]['config']['row'];

        if ( file_exists( $gatewayConfiguration ) ) {
            $jsonData = json_decode( file_get_contents( $gatewayConfiguration ) );
            if ( ! empty( $jsonData ) && ! empty( $jsonData->$gateway ) ) {
                $array = get_object_vars( $jsonData->$gateway );
                foreach ( array_keys( $array ) as $key ) {
                    if ($key === $gatewayConfigurationRow) {
                        $array[$key] = $paymentAction;
                    }
                }
            }
        }
        return serialize($array);
    }
}
