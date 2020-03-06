<?php

namespace Helper\Config\Customer;

/**
 * Class CustomerConfig
 */
class CustomerConfig
{
    private $lastName;

    private $firstName;

    private $country;

    private $streetAddress;

    private $town;

    private $postCode;

    private $phone;

    private $emailAddress;

    private $password;

    private $loginUsername;

    /**
     * CustomerConfig constructor.
     * @param $customerData
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
        $this->loginUsername = $customerData->login_username;
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

    /**
     * @return mixed
     */
    public function getLoginUsername()
    {
        return $this->loginUsername;
    }
}
