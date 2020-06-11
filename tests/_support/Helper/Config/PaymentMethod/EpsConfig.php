<?php

namespace Helper\Config\PaymentMethod;

/**
 * Class EpsConfig
 */
class EpsConfig
{

    private $bic;

    /**
     * EpsConfig constructor.
     * @param $epsData
     */
    public function __construct($epsData)
    {
        $this->bic = $epsData->bic;
    }

    /**
     * @return mixed
     */
    public function getBic()
    {
        return $this->bic;
    }
}
