<?php
namespace Qc\QcInfoRights\Filter;
class Filter
{
    /**
     * @var string
     */
    protected string $username='';

    /**
     * @var string
     */
    protected string $mail='';

    /**
     * @var int
     */
    protected int $hideInactiveUsers = 0;

    /**
     * @var int
     */
    protected int $currentUsersTabPage = 1;



    /**
     * @var array
     */
    protected array $orderArray = [];

    /*
     * @var string
     */
    protected string $rejectUserStartWith = '';


    /**
     * @var int
     */
    protected int $currentGroupsTabPage = 1;

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getMail(): string
    {
        return $this->mail;
    }

    /**
     * @param string $mail
     */
    public function setMail(string $mail): void
    {
        $this->mail = $mail;
    }



    /**
     * @return int
     */
    public function getCurrentUsersTabPage(): int
    {
        return $this->currentUsersTabPage;
    }

    /**
     * @param int $currentUsersTabPage
     */
    public function setCurrentUsersTabPage(int $currentUsersTabPage): void
    {
        $this->currentUsersTabPage = $currentUsersTabPage >= 1  ? $currentUsersTabPage : 1;
    }

    /**
     * @return int
     */
    public function getCurrentGroupsTabPage(): int
    {
        return $this->currentGroupsTabPage;
    }

    /**
     * @param int $currentGroupsTabPage
     */
    public function setCurrentGroupsTabPage(int $currentGroupsTabPage): void
    {
        $this->currentGroupsTabPage = $currentGroupsTabPage >= 1 ? $currentGroupsTabPage : 1;
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

    /**
     * @return string
     */
    public function getRejectUserStartWith(): string
    {
        return $this->rejectUserStartWith;
    }

    /**
     * @param string $rejectUserStartWith
     */
    public function setRejectUserStartWith(string $rejectUserStartWith): void
    {
        $this->rejectUserStartWith = $rejectUserStartWith;
    }

    /**
     * @return int
     */
    public function getHideInactiveUsers(): int
    {
        return $this->hideInactiveUsers;
    }

    /**
     * @param int $hideInactiveUsers
     */
    public function setHideInactiveUsers(int $hideInactiveUsers): void
    {
        $this->hideInactiveUsers = $hideInactiveUsers;
    }



}
