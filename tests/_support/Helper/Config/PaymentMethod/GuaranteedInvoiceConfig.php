<?php

namespace Helper\Config\PaymentMethod;

/**
 * Class GuaranteedInvoiceConfig
 */
class GuaranteedInvoiceConfig
{

    private $dateOfBirth;

    /**
     * GuaranteedInvoiceConfig constructor.
     * @param $guaranteedInvoiceData
     */
    public function __construct($guaranteedInvoiceData)
    {
        $this->dateOfBirth = $guaranteedInvoiceData->date_of_birth;
    }

    /**
     * @return mixed
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }
}
