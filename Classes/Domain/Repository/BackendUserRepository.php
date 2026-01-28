<?php
namespace Qc\QcInfoRights\Domain\Repository;

/***
 *
 * This file is part of Qc Info rights project.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2026 <techno@quebec.ca>
 *
 ***/

use Qc\QcInfoRights\Domain\Model\Demand;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class BackendUserRepository
 *
 * @package \\${NAMESPACE}
 */
class BackendUserRepository extends  Repository{

    /**
     * @var array Default order is by LastLogin ascending
     */
    protected $defaultOrderings = [
        'lastlogin' => QueryInterface::ORDER_DESCENDING,
        'userName' => QueryInterface::ORDER_ASCENDING
    ];

    /**
     * Overwrite createQuery to don't respect enable fields
     *
     * @return QueryInterface
     */
    public function createQuery()
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->setOrderings($this->defaultOrderings);
        return $query;
    }

    /**
     * @return SessionBackendInterface
     */
    protected function getSessionBackend(): SessionBackendInterface
    {
        return GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend('BE');
    }

    /**
     * Find Backend Users matching to Demand object properties
     * @param Demand $demand
     * @return QueryResult
     * @throws InvalidQueryException
     */
    public function findDemanded(Demand $demand): QueryResult
    {
        # Breaking change
        # https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-96044-HardenMethodSignatureOfLogicalAndAndLogicalOr.html
        # logicalAnd n'accepte plus les array

        $constraints = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $query = $this->createQuery();
        $query->setOrderings($this->defaultOrderings);

        if(!empty($demand->getOrderArray())){
            $orderConfig = $demand->getOrderArray();
            $newOrderArray = [];
            for($i = 0; $i< count($orderConfig); $i++){
                $newOrderArray[$orderConfig[$i][0]] = $orderConfig[$i][1];
            }
            $query->setOrderings($newOrderArray);
        }

        // Username
        if ($demand->getUserName() !== '') {
            $searchConstraints = [];
            foreach (['userName', 'realName'] as $field) {
                $searchConstraints[] = $query->like(
                    $field,
                    '%' . $queryBuilder->escapeLikeWildcards($demand->getUserName()) . '%'
                );
            }
            if (MathUtility::canBeInterpretedAsInteger($demand->getUserName())) {
                $searchConstraints[] = $query->equals('uid', (int)$demand->getUserName());
            }
            $constraints[] = $query->logicalOr(...$searchConstraints);
        }

        /**Check if reject User start with Special char like "_cli_"*/
        if($demand->getRejectUserStartWith() != ''){
            $constraints[] = $query->logicalNot(
                $query->like('userName',  $queryBuilder->escapeLikeWildcards($demand->getRejectUserStartWith()).'%'),
            );
            $constraints[] = $query->logicalNot(
                $query->like('realName',  $queryBuilder->escapeLikeWildcards($demand->getRejectUserStartWith()).'%')
            );
        }

        if($demand->getEmail() != ''){
            $constraints[] = $query->like('email', '%' . $queryBuilder->escapeLikeWildcards(str_replace(' ', '', $demand->getEmail())) . '%');
        }

        // Only display admin users
        if ($demand->getUserType() == Demand::USERTYPE_ADMINONLY) {
            $constraints[] = $query->equals('admin', 1);
        }
        // Only display non-admin users
        if ($demand->getUserType() == Demand::USERTYPE_USERONLY) {
            $constraints[] = $query->equals('admin', 0);
        }
        // Only display active users
        if ($demand->getStatus() == Demand::STATUS_ACTIVE) {
            $constraints[] = $query->equals('disable', 0);
        }
        // Only display in-active users
        if ($demand->getStatus() == Demand::STATUS_INACTIVE) {
            $constraints[] = $query->logicalOr($query->equals('disable', 1));
        }
        // Not logged in before
        if ($demand->getLogins() == Demand::LOGIN_NONE) {
            $constraints[] = $query->equals('lastlogin', 0);
        }
        // At least one login
        if ($demand->getLogins() == Demand::LOGIN_SOME) {
            $constraints[] = $query->logicalNot($query->equals('lastlogin', 0));
        }
        // In backend user group
        // @TODO: Refactor for real n:m relations
        if ($demand->getBackendUserGroup()) {
            $constraints[] = $query->logicalOr(
                $query->equals('usergroup', (int)$demand->getBackendUserGroup()),
                $query->like('usergroup', (int)$demand->getBackendUserGroup() . ',%'),
                $query->like('usergroup', '%,' . (int)$demand->getBackendUserGroup()),
                $query->like('usergroup', '%' . (int)$demand->getBackendUserGroup() . ',%')
            );
        }
        $query->matching($query->logicalAnd(...$constraints));

        /** @var QueryResult $result */
        $result = $query->execute();
        return $result;
    }

    /**
     * This Function is used to render the members of the selected group
     * @return array
     */
    public function getGroupMembers(int $groupUid, string $selectedColumn = 'username'): array
    {
        $allowedColumns = ['username', 'email', 'realName'];
        // make sur that the selected Column is allowed
        if(!in_array($selectedColumn, $allowedColumns)){
            $selectedColumn = 'username';
        }
        $groupMembers = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $statement = $queryBuilder
            ->select('uid','username','email','realName')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('usergroup', $queryBuilder->createNamedParameter($groupUid, Connection::PARAM_INT))
            )->orderBy($selectedColumn)->executeQuery();
        while ($row = $statement->fetchAssociative()) {
            array_push($groupMembers, [
                'uid' => $row['uid'],
                'username' => $row['username'],
                'realName' => $row['realName'],
                'email' => $row['email']
            ]);
        }
        return $groupMembers;
    }

}
