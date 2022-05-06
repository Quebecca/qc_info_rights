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

use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class AccessRightsReport extends \Qc\QcInfoRights\Report\QcInfoRightsReport
{

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
            'label' => $this->getLanguageService()->getLL('accessRights'),
            'content' => $this->createViewForAccessRightsTab()->render()
        ];

        return $this->moduleTemplate->getDynamicTabMenu($menuItems, 'report-qcinforights');
    }

    /**
     * Displays the View of Access and Rights
     *
     * @return StandaloneView
     */
    protected function createViewForAccessRightsTab()
    {
        $view = $this->createView('AccessRightsTab');
        $selectBox = BackendUtility::getDropdownMenu($this->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth']);

        /*Render Tree View For the Access Page with His Depth*/
        /** @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->addField('perms_user', true);
        $tree->addField('perms_group', true);
        $tree->addField('perms_everybody', true);
        $tree->addField('perms_userid', true);
        $tree->addField('perms_groupid', true);
        $tree->addField('hidden');
        $tree->addField('fe_group');
        $tree->addField('starttime');
        $tree->addField('endtime');
        $tree->addField('editlock');

        if ($this->id) {
            $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => $tree->getIcon($this->id)];
        } else {
            $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => $tree->getRootIcon($this->pageInfo)];
        }
        $tree->getTree($this->id, $this->pObj->MOD_SETTINGS['depth']);

        $beUserArray = BackendUtility::getUserNames();
        $beGroupArray = BackendUtility::getGroupNames();

        $view->assignMultiple([
            'prefix' => 'accessRights',
            'viewTree' => $tree->tree,
            'depthSelect' => $selectBox,
            'hideUser' => $this->checkShowColumnTsConfig('user'),
            'hideGroup' => $this->checkShowColumnTsConfig('group'),
            'hideEveryBody' => $this->checkShowColumnTsConfig('everybody'),
            'beUsers' => $beUserArray,
            'beGroups' => $beGroupArray
        ]);

        return $view;
    }


}
