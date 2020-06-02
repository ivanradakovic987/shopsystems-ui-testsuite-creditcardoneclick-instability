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
     * @param $invoiceData
     */
    public function __construct($invoiceData)
    {
        $this->dateOfBirth = $invoiceData->date_of_birth;
    }

    /**
     * @return mixed
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }
}
