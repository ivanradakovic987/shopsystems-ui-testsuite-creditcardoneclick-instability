<?php

namespace Helper\Config\PaymentMethod;

/**
 * Class GiropayConfig
 */
class GiropayConfig
{

    private $bic;

    private $sc;

    private $extension_sc;

    private $customer_name;

    private $customer_iban;

    /**
     * GiropayConfig constructor.
     * @param $giropayData
     */
    public function __construct($giropayData)
    {
        $this->bic = $giropayData->bic;
        $this->sc = $giropayData->sc;
        $this->extension_sc = $giropayData->extension_sc;
        $this->customer_name = $giropayData->customer_name;
        $this->customer_iban = $giropayData->customer_iban;
    }

    /**
     * @return mixed
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * @return mixed
     */
    public function getSc()
    {
        return $this->sc;
    }

    /**
     * @return mixed
     */
    public function getExtensionSc()
    {
        return $this->extension_sc;
    }

    /**
     * @return mixed
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * @return mixed
     */
    public function getCustomerIban()
    {
        return $this->customer_iban;
    }
}
