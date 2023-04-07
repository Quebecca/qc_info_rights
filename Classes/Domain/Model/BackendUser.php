<?php
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
namespace Qc\QcInfoRights\Domain\Model;

/**
 * Class BackendUser
 *
 * @package \Qc\QcInfoRights\Domain\Model
 */
class BackendUser extends \TYPO3\CMS\Beuser\Domain\Model\BackendUser{

    /**
     * @var string
     */
    protected $crdate = '';

    /**
     * @param string|null $crdate
     */
    public function setCrdate(string $crdate)
    {
        $this->crdate = $crdate;
    }

    /**
     * @return string
     */
    public function getCrdate()
    {
       return  $this->crdate;
    }
}
