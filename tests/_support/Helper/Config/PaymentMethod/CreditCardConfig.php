<?php

namespace Helper\Config\PaymentMethod;

/**
 * Class CreditCardConfig
 */
class CreditCardConfig
{

    private $firstName;

    private $lastName;

    private $cardNumber;

    private $cvv;

    private $validUntil;

    private $password;

    public $paymentMethodName = 'creditCard';


    /**
     * CreditCardConfig constructor.
     * @param $creditCardData
     */
    public function __construct($creditCardData)
    {
        $this->firstName = $creditCardData->first_name;
        $this->lastName = $creditCardData->last_name;
        $this->cardNumber = $creditCardData->card_number;
        $this->cvv = $creditCardData->cvv;
        $this->validUntil = $creditCardData->valid_until;
        $this->password = $creditCardData->password;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
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
    public function getCardNumber()
    {
        return $this->cardNumber;
    }

    /**
     * @return mixed
     */
    public function getCvv()
    {
        return $this->cvv;
    }

    /**
     * @return mixed
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }

    /**
     * @return string
     */
    public function getPaymentMethodName(): string
    {
        return $this->paymentMethodName;
    }

}