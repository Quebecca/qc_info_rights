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
namespace Qc\QcInfoRights\Report;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

class AccessRightsReport extends \Qc\QcInfoRights\Report\QcInfoRightsReport
{
    protected $pageInfo;

    /**
     * @param InfoModuleController $pObj
     */
    public function init($pObj)
    {
        parent::init($pObj);
        $this->pObj = $pObj;
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
        $this->setPageInfo();

    }

    /**
     * @return string
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
     * @return StandaloneView
     * Displays the View of Access and Rights
     */
    protected function createViewForAccessRightsTab(): StandaloneView
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

    /**
     * Returns the menu array
     *
     * @return array
     */
    protected function modMenu(): array
    {
        $menu = [
            'pages' => [],
            'depth' => [
                0 => $this->getLanguageService()->sL(self::prefix_core_lang.'labels.depth_0'),
                1 => $this->getLanguageService()->sL(self::prefix_core_lang.'labels.depth_1'),
                2 => $this->getLanguageService()->sL(self::prefix_core_lang.'labels.depth_2'),
                3 => $this->getLanguageService()->sL(self::prefix_core_lang.'labels.depth_3'),
                4 => $this->getLanguageService()->sL(self::prefix_core_lang.'labels.depth_4'),
                999 => $this->getLanguageService()->sL(self::prefix_core_lang.'labels.depth_infi')
            ]
        ];

        // Using $GLOBALS['TYPO3_REQUEST'] since $request is not available at this point
        // @todo: Refactor mess and have $request available
        $this->fillFieldConfiguration($this->id, $GLOBALS['TYPO3_REQUEST']);
        foreach ($this->fieldConfiguration as $key => $item) {
            $menu['pages'][$key] = $item['label'];
        }
        return $menu;
    }

    /**
     * Generate configuration for field selection
     *
     * @param int $pageId current page id
     */
    protected function fillFieldConfiguration(int $pageId, ServerRequestInterface $request)
    {
        $modTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_info.']['fieldDefinitions.'] ?? [];
        foreach ($modTSconfig as $key => $item) {
            $fieldList = str_replace('###ALL_TABLES###', $this->cleanTableNames(), (string) $item['fields']);
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            $key = trim((string) $key, '.');
            $this->fieldConfiguration[$key] = [
                'label' => $item['label'] ? $this->getLanguageService()->sL($item['label']) : $key,
                'fields' => $fields
            ];
        }
    }

    /**
     * Function, which returns all tables to
     * which the user has access. Also a set of standard tables (pages, sys_filemounts, etc...)
     * are filtered out. So what is left is basically all tables which makes sense to list content from.
     *
     * @return string
     */
    protected function cleanTableNames(): string
    {
        // Get all table names:
        $tableNames = array_flip(array_keys($GLOBALS['TCA']));
        // Unset common names:
        unset($tableNames['pages']);
        unset($tableNames['sys_filemounts']);
        unset($tableNames['sys_action']);
        unset($tableNames['sys_workflows']);
        unset($tableNames['be_users']);
        unset($tableNames['be_groups']);
        $allowedTableNames = [];
        // Traverse table names and set them in allowedTableNames array IF they can be read-accessed by the user.
        if (is_array($tableNames)) {
            foreach ($tableNames as $k => $v) {
                if (!($GLOBALS['TCA'][$k]['ctrl']['hideTable'] ?? false)
                    && $this->getBackendUser()->check('tables_select', $k)) {
                    $allowedTableNames['table_' . $k] = $k;
                }
            }
        }
        return implode(',', array_keys($allowedTableNames));
    }

    /**
     * This function to check if get default or Custom Value
     *
     *
     * @return string
     */
    protected function checkShowColumnTsConfig(string $value): string
    {
        if (is_array($this->userTS['qcinforights.'] ?? null)
            && array_key_exists($value, $this->userTS['qcinforights.']['hideAccessRights.'] ?? [])) {
            return $this->userTS['qcinforights.']['hideAccessRights.'][$value];
        } else if (is_array($this->modTSconfig['properties']) && array_key_exists($value, $this->modTSconfig['properties']['hideAccessRights.'])) {
            return $this->modTSconfig['properties']['hideAccessRights.'][$value];
        }
        return '';
    }

    /**
     * Check if page record exists and set pageInfo
     */
    protected function setPageInfo(): void
    {
        $this->pageInfo = BackendUtility::readPageAccess(BackendUtility::getRecord('pages', $this->id) ? $this->id : 0, ' 1=1');
    }

}
