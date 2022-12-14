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

namespace Qc\QcInfoRights\Domain\Model;

use Qc\QcInfoRights\Domain\Model\Demand;


/**
 * Module data object
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ModuleData
{

    protected Demand $demand;

    public function __construct()
    {
        $this->demand = new Demand();
    }

    public function getDemand(): Demand
    {
        return $this->demand;
    }

}
