<?php

namespace Helper\Config;

class Filesystem
{
    // @TODO: don't need to add the visibility if it is public - because of default
    public const CONFIG_FILE = DIRECTORY_SEPARATOR . 'config.json';
   // define('CONFIG_FILE', getcwd() . DIRECTORY_SEPARATOR . 'config.json');

    public const DATA_FOLDER_PATH =  DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR;
    //define('DATA_FOLDER_PATH', getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR);

    public const CUSTOMER_DATA_FOLDER_PATH = self::DATA_FOLDER_PATH . 'Customer' . DIRECTORY_SEPARATOR;
    //define('CUSTOMER_DATA_FOLDER_PATH', DATA_FOLDER_PATH . 'Customer' . DIRECTORY_SEPARATOR);

    public const LOCATOR_FOLDER_PATH = self::DATA_FOLDER_PATH . 'Locator' . DIRECTORY_SEPARATOR;
    //define('LOCATOR_FOLDER_PATH', DATA_FOLDER_PATH . 'Locator' . DIRECTORY_SEPARATOR);

    public const SHOP_SYSTEM_LOCATOR_FOLDER_PATH = self::LOCATOR_FOLDER_PATH . 'ShopSystem' . DIRECTORY_SEPARATOR;
    //define('SHOP_SYSTEM_LOCATOR_FOLDER_PATH', LOCATOR_FOLDER_PATH . 'ShopSystem' . DIRECTORY_SEPARATOR);

    public const PAYMENT_METHOD_LOCATOR_FOLDER_PATH = self::LOCATOR_FOLDER_PATH . 'PaymentMethod' . DIRECTORY_SEPARATOR;
    //define('PAYMENT_METHOD_LOCATOR_FOLDER_PATH', LOCATOR_FOLDER_PATH . 'PaymentMethod' . DIRECTORY_SEPARATOR);


    public const PAYMENT_METHOD_DATA_FOLDER_PATH = self::DATA_FOLDER_PATH . 'PaymentMethodData' . DIRECTORY_SEPARATOR;
    //define('PAYMENT_METHOD_DATA_FOLDER_PATH', DATA_FOLDER_PATH . 'PaymentMethodData' . DIRECTORY_SEPARATOR);

    public const PAYMENT_METHOD_CONFIG_FOLDER_PATH = self::DATA_FOLDER_PATH . 'PaymentMethodConfig' . DIRECTORY_SEPARATOR;
    //define('PAYMENT_METHOD_CONFIG_FOLDER_PATH', DATA_FOLDER_PATH . 'PaymentMethodConfig' . DIRECTORY_SEPARATOR);

}