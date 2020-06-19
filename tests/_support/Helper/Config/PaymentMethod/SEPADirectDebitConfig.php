<?php

namespace Helper\Config\PaymentMethod;

/**
 * Class SEPADirectDebitConfig
 */
class SEPADirectDebitConfig
{
    private $firstName;
    private $lastName;
    private $iban;

    /**
     * SEPADirectDebitConfig constructor.
     * @param $sepaData
     */
    public function __construct($sepaData)
    {
        $this->iban = $sepaData->iban;
        $this->firstName = $sepaData->first_name;
        $this->lastName = $sepaData->last_name;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return mixed
     */
    public function getIban()
    {
        return $this->iban;
    }
}
