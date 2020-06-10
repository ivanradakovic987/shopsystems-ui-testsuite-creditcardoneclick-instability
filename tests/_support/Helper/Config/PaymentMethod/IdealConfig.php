<?php

namespace Helper\Config\PaymentMethod;

/**
 * Class IdealConfig
 */
class IdealConfig
{

    private $selectBank;

    /**
     * IdealConfig constructor.
     * @param $idealData
     */
    public function __construct($idealData)
    {
        $this->selectBank = $idealData->bank;
    }

    /**
     * @return mixed
     */
    public function getBank()
    {
        return $this->selectBank;
    }
}
