<?php

namespace Helper\Config\PaymentMethod;

/**
 * Class SofortConfig
 */
class SofortConfig
{

    private $selectBank;

    private $kontonummer;

    private $pin;

    private $tan;

    /**
     * SofortConfig constructor.
     * @param $sofortData
     */
    public function __construct($sofortData)
    {
        $this->selectBank = $sofortData->bank_select;
        $this->kontonummer = $sofortData->kontonummer;
        $this->pin = $sofortData->pin;
        $this->tan = $sofortData->tan;
    }

    /**
     * @return mixed
     */
    public function getDemoBank()
    {
        return $this->selectBank;
    }

    /**
     * @return mixed
     */
    public function getKontonummer()
    {
        return $this->kontonummer;
    }

    /**
     * @return mixed
     */
    public function getPin()
    {
        return $this->pin;
    }

    /**
     * @return mixed
     */
    public function getTan()
    {
        return $this->tan;
    }
}
