<?php

namespace Helper\Config\PaymentMethod;

/**
 * Class PayPalConfig
 */
class PayPalConfig
{

    private $userName;

    private $password;

    /**
     * PayPalConfig constructor.
     * @param $payPalData
     */
    public function __construct($payPalData)
    {
        $this->userName = $payPalData->user_name;
        $this->password = $payPalData->password;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }
}
