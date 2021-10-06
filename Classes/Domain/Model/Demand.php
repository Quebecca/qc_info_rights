<?php

namespace Qc\QcInfoRights\Domain\Model;

class Demand extends \TYPO3\CMS\Beuser\Domain\Model\Demand{

    /*
     * @var string
     */
    protected $rejectUserStartWith = '';

    /**
     * @var array
     */
    protected $orderArray = [];

    /*
     * @var string
     */
    protected $email = '';


    /*
    * Setter to set the reject user start with value
    */
    public function setRejectUserStartWith(string $rejectUserStartWith)
    {
        $this->rejectUserStartWith = $rejectUserStartWith;
    }

    /*
    * Getter method to get value of rejected user start with
    */
    public function getRejectUserStartWith(): string
    {
        return $this->rejectUserStartWith;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /*
    * Getter method to get value of rejected user start with
    */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return array
     */
    public function getOrderArray(): array
    {
        return $this->orderArray;
    }

    /**
     * @param array $orderArray
     */
    public function setOrderArray(array $orderArray): void
    {
        $this->orderArray = $orderArray;
    }

}
