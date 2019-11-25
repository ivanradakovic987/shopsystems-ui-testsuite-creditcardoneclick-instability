<?php

namespace Helper\Config\Customer;

/**
 * Class CustomerConfig
 */
class CustomerConfig
{
    /**
     * @var
     */
    private $lastName;
    /**
     * @var
     */
    private $firstName;
    /**
     * @var
     */
    private $country;
    /**
     * @var
     */
    private $streetAddress;
    /**
     * @var
     */
    private $town;
    /**
     * @var
     */
    private $postCode;
    /**
     * @var
     */
    private $phone;
    /**
     * @var
     */
    private $emailAddress;
    /**
     * @var
     */
    private $password;

    /**
     * CustomerConfig constructor.
     */
    public function __construct($customerData)
    {
        $this->firstName = $customerData->first_name;
        $this->lastName = $customerData->last_name;
        $this->country = $customerData->country;
        $this->streetAddress = $customerData->street_address;
        $this->town = $customerData->town;
        $this->postCode = $customerData->post_code;
        $this->phone = $customerData->phone;
        $this->emailAddress = $customerData->email_address;
        $this->password = $customerData->password;
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
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return mixed
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * @return mixed
     */
    public function getTown()
    {
        return $this->town;
    }

    /**
     * @return mixed
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return mixed
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

}