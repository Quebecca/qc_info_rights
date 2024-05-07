<?php

declare(strict_types=1);

/***
 *
 * This file is part of Qc Info rights project.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2022 <techno@quebec.ca>
 *
 ***/
namespace Qc\QcInfoRights\BackendSession;


use __PHP_Incomplete_Class;
use phpDocumentor\Reflection\Types\String_;
use Qc\QcInfoRights\Filter\Filter;
use Qc\QcInfoRights\Util\Arrayable;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendSession
{
    /**
     * The backend session object
     *
     * @var BackendUserAuthentication
     */
    protected $sessionObject;

    /** @var string[] */
    protected $registeredKeys = [];

    /**
     * @var int
     */
    protected int $typoVersion;

    /**
     * Unique key to store data in the session.
     * Overwrite this key in your initializeAction method.
     *
     * @var string
     */
    protected $storageKey = 'qc_info_rights_key';

    public function __construct()
    {
        $this->sessionObject = $GLOBALS['BE_USER'];
        $this->registerFilterKey('qc_info_rights_key', Filter::class);
        $this->typoVersion = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();

    }

    /**
     * This function is used to register keys
     */
    public function registerFilterKey(string $key, string $class): void
    {
        if (!$this->isClassImplementsInterface($class, Arrayable::class)) {
            throw new \InvalidArgumentException('Given class not instance of Arrayable');
        }
        $this->registeredKeys[$key] = $class;
    }

    /**
     * This function is used to verify if the class implements the interface Arrayable
     * @return bool
     */
    protected function isClassImplementsInterface(string $class, string $interface): bool
    {
        $interfaces = class_implements($class);
        if ($interfaces && in_array($interface, $interfaces)) {
            return true;
        }
        return false;
    }

    /**
     * @param $storageKey
     */
    public function setStorageKey($storageKey)
    {
        $this->storageKey = $storageKey;
    }

    /**
     * Store a value in the session
     */
    public function store(string $key, mixed $value)
    {

        if (!isset($this->registeredKeys[$key])) {
            throw new \InvalidArgumentException('Unknown key ' . $key);
        }
        $sessionData = $this->sessionObject->getSessionData($this->storageKey);
        if ($this->typoVersion >= 11 && isset($this->registeredKeys[$key])) {
            $valueArray = $value->toArray();
            $sessionData[$key] = $valueArray;
        } else {
            $sessionData[$key] = $value;
        }
        $this->sessionObject->setAndSaveSessionData($this->storageKey, $sessionData);
    }

    /**
     * Delete a value from the session
     */
    public function delete(string $key)
    {
        $sessionData = $this->sessionObject->getSessionData($this->storageKey);
        unset($sessionData[$key]);
        $this->sessionObject->setAndSaveSessionData($this->storageKey, $sessionData);
    }

    /**
     * @return false|mixed|Arrayable|null
     */
    public function get(string $key)
    {
        $sessionData = $this->sessionObject->getSessionData($this->storageKey);

        if (!isset($sessionData[$key]) || !$sessionData[$key]) {
            return null;
        }
        $result = $sessionData[$key];
        if($this->typoVersion == 10)
            return $result;
        // safeguard: check for incomplete class
        if (is_object($result) && is_a($result, __PHP_Incomplete_Class::class)) {
            $this->delete($key);
            return null;
        }
        if (is_object($result) && is_a($result, Arrayable::class)) {
            return $result;
        }
        if (is_array($result) && isset($this->registeredKeys[$key])) {
            return call_user_func([$this->registeredKeys[$key], 'getInstanceFromArray'], $result);
        }
        return null;
    }
}
