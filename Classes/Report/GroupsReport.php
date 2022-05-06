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
namespace Qc\QcInfoRights\Report;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

class GroupsReport extends QcInfoRightsReport
{

    /**
     * Initializes the Module
     */
    protected function initialize()
    {
        parent::initialize();
        $pageRenderer = $this->moduleTemplate->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/QcInfoRights/ShowMembers');
    }

    /**
     * Create tabs to split the report and the checkLink functions
     */
    protected function renderContent(): string
    {
        if (!$this->isAccessibleForCurrentUser) {
            // If no access or if ID == zero
            $this->moduleTemplate->addFlashMessage(
                $this->getLanguageService()->getLL('no.access'),
                $this->getLanguageService()->getLL('no.access.title'),
                FlashMessage::ERROR
            );
            return '';
        }
        $menuItems = [];
        $menuItems[] = [
            'label' => $this->getLanguageService()->getLL('beUserGroupsLists'),
            'content' => $this->createViewForBeUserGroupListTab()->render()
        ];
        return $this->moduleTemplate->getDynamicTabMenu($menuItems, 'report-qcinforights');
    }


    /**
     * Displays the View for the Backend User List
     *
     * @return StandaloneView
     */
    protected function createViewForBeUserGroupListTab()
    {
        $this->filter = $this->backendSession->get('qc_info_rights_key');
        if (GeneralUtility::_GP('groupPaginationPage') != null ){
            $groupPaginationCurrentPage = (int)GeneralUtility::_GP('groupPaginationPage');
            // Store the current page on session
            $this->filter = $this->backendSession->get('qc_info_rights_key');
            $this->filter->setCurrentGroupsTabPage($groupPaginationCurrentPage);
            $this->updateFilter();
        }
        else{
            // read from Session
            $groupPaginationCurrentPage = $this->filter->getCurrentGroupsTabPage();
        }
        $view = $this->createView('BeUserGroupList');
        $groupsWithNumberOfUsers = [];
        $pagination = $this->getPagination($this->backendUserGroupRepository->findAll(), $groupPaginationCurrentPage,$this->groupsPerPage );
        $groups = $pagination['paginatedData'];
        foreach ($groups as $group){
            array_push($groupsWithNumberOfUsers, [
                'group' => $group,
                'numberOfUsers' => count($this->backendUserRepository->getGroupMembers($group->getUid()))
            ]);
        }
        $view->assignMultiple([
            'prefix' => 'beUserGroupList',
            'backendUserGroups' => $groupsWithNumberOfUsers,
            'showExportGroups' => $this->showExportGroups,
            'showMembersColumn' => $this->checkShowTsConfig('showMembersColumn'),
            'pagination' => $pagination['pagination'],
            'currentPage' => $this->id,
            'args' => $this->set
        ]);
        return $view;
    }

    // show members
    /**
     * This Function is delete the selected excluded link
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function showMembers(ServerRequestInterface $request): ResponseInterface{
        $urlParam = $request->getQueryParams();
        $members = $this->backendUserRepository->getGroupMembers($urlParam['groupUid'], $urlParam['selectedColumn']);
        return new JsonResponse($members);
    }

}
