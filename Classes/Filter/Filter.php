<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
namespace Qc\QcInfoRights\Filter;
use Qc\QcInfoRights\Util\Arrayable;

class Filter implements Arrayable
{
    protected const KEY_USERNAME = 'username';
    protected const KEY_MAIL = 'mail';
    protected const KEY_HIDEINACTIVEUSERES = 'hideInactiveUsers';
    protected const KEY_CURRENTUSERSTABPAGE = 'currentUsersTabPage';
    protected const KEY_ORDERARRAY = 'orderArray';
    protected const KEY_REJECTUSERSTARTWITH = 'rejectUserStartWith';
    protected const KEY_CURRENTGROUPSTABPAGE = 'currentGroupsTabPage';

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

    public function toArray()
    {
        return [
            self::KEY_USERNAME => $this->getUsername(),
            self::KEY_MAIL => $this->getMail(),
            self::KEY_HIDEINACTIVEUSERES => $this->getHideInactiveUsers(),
            self::KEY_CURRENTUSERSTABPAGE => $this->getCurrentUsersTabPage(),
            self::KEY_ORDERARRAY => $this->getOrderArray(),
            self::KEY_REJECTUSERSTARTWITH => $this->getRejectUserStartWith(),
            self::KEY_CURRENTGROUPSTABPAGE => $this->getCurrentGroupsTabPage()
        ];
    }

    public static function getInstanceFromArray(array $values)
    {
        $filter =  new Filter();
        $filter->setUsername(self::KEY_USERNAME);
        $filter->setMail(self::KEY_MAIL);
        $filter->setHideInactiveUsers(intval(self::KEY_HIDEINACTIVEUSERES));
        $filter->setCurrentUsersTabPage(intval(self::KEY_CURRENTUSERSTABPAGE));
        $filter->setCurrentGroupsTabPage(intval(self::KEY_CURRENTGROUPSTABPAGE));
        $filter->setOrderArray([self::KEY_ORDERARRAY]);
        $filter->setRejectUserStartWith(self::KEY_REJECTUSERSTARTWITH);
        return $filter;
    }
}
