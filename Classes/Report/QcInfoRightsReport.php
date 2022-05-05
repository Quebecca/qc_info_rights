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

use Psr\Http\Message\ServerRequestInterface;
use Qc\QcInfoRights\BackendSession\BackendSession;
use Qc\QcInfoRights\Domain\Model\ModuleData;
use Qc\QcInfoRights\Domain\Repository\BackendUserRepository;
use Qc\QcInfoRights\Filter\Filter;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;
use Psr\Http\Message\ResponseInterface;
/**
 * Module 'Qc info rights' as sub module of Web -> Info
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class QcInfoRightsReport
{
    /**
     * @var string
     */
    const KEY = 'tx_beuser';

    /**
     * @var string
     */
    const prefix_be_user_lang = 'LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:';

    /**
     * @var string
     */
    const prefix_core_lang = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:';

    /***
     * @var  string
     */
    const prefix_filter = 'user';

    /**
     * Array configuration for the order of Table backend user list
     */
    protected const ORDER_BY_VALUES = [
        'lastLogin' => [
            ['lastlogin', 'ASC'],
        ],
        'lastLogin_reverse' => [
            ['lastlogin', 'DESC'],
        ],
        'userName' => [
            ['userName', 'ASC'],
        ],
        'userName_reverse' => [
            ['userName', 'DESC'],
        ],
        'email' => [
            ['email' , 'ASC'],
        ],
        'email_reverse' => [
            ['email' , 'DESC'],
        ],
        'disable_compare' => [
            ['disable' , 'ASC'],
        ],
        'disable_compare_reverse' => [
            ['disable' , 'DESC'],
        ],
    ];

    /**
     * @var \Qc\QcInfoRights\Domain\Model\ModuleData
     */
    protected $moduleData;

    /**
     * Information about the current page record
     *
     * @var array
     */
    protected $pageRecord = [];

    /**
     * Information, if the module is accessible for the current user or not
     *
     * @var bool
     */
    protected $isAccessibleForCurrentUser = false;

    /**
     * TSconfig of the current module
     *
     * @var array
     */
    protected $modTS = [];

    /**
     * TSconfig of the current User Backend
     *
     * @var array
     */
    protected $userTS = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * @var string Value of the GET/POST var orderBy
     */
    protected $orderBy;

    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    /**
     * @var PageRepository
     */
    protected $pagesRepository;

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var BackendUserRepository
     */
    protected $backendUserRepository;

    /**
     * @var BackendUserGroupRepository
     */
    protected $backendUserGroupRepository;

    /**
     * @var boolean
     */
    protected $showTabAccess;

    /**
     * @var boolean
     */
    protected $showTabUsers;

    /**
     * @var boolean
     */
    protected $showTabGroups;

    /**
     * @var boolean
     */
    protected $showExportUsers;

    /**
     * @var boolean
     */
    protected $showExportGroups;

    /**
     * @var boolean
     */
    protected $showAdministratorUser;

    /**
     * Module TSconfig based on PAGE TSconfig / USER TSconfig
     *
     * @var array
     */
    protected $modTSconfig;

    /**
     * all params pass with 'prefix_user._SET'
     *
     * @var array
     */
    protected $set = [];

    /**
     * @var Filter
     */
    protected Filter $filter;

    /**
     * @var BackendSession
     */
    protected BackendSession $backendSession;

    /**
     * @var int
     */
    protected int  $usersPerPage = 100;

    /**
     * @var int
     */
    protected int  $groupsPerPage = 100;

    /**
     * QcInfoRightsReport constructor.
     *
     * @param \TYPO3\CMS\Core\Domain\Repository\PageRepository|null               $pagesRepository
     * @param \TYPO3\CMS\Backend\Routing\UriBuilder|null                          $uriBuilder
     * @param \TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository|null $backendUserGroupRepository
     * @param \TYPO3\CMS\Extbase\Utility\LocalizationUtility|null                 $localizationUtility
     * @param \TYPO3\CMS\Core\Charset\CharsetConverter|null                       $charsetConverter
     */
    public function __construct(
        PageRepository $pagesRepository = null,
        UriBuilder $uriBuilder = null,
        BackendUserGroupRepository $backendUserGroupRepository = null,
        LocalizationUtility $localizationUtility = null,
        CharsetConverter $charsetConverter = null
    )
    {
        $this->pagesRepository = $pagesRepository ?? GeneralUtility::makeInstance(PageRepository::class);
        $this->uriBuilder = $uriBuilder ?? GeneralUtility::makeInstance(UriBuilder::class);
        $this->backendUserGroupRepository = $backendUserGroupRepository ?? GeneralUtility::makeInstance(BackendUserGroupRepository::class);
        $this->localizationUtility = $localizationUtility ?? GeneralUtility::makeInstance(LocalizationUtility::class);
        $this->charsetConverter = $charsetConverter ?? GeneralUtility::makeInstance(CharsetConverter::class);

        //Initialize Repository Backend user
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
        $this->backendUserRepository = GeneralUtility::makeInstance(BackendUserRepository::class, $this->objectManager);
        $this->backendUserRepository->injectPersistenceManager($persistenceManager);

        /*Initialize the TsConfig Array*/
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['qcinforights.'] ?? [];

        /*Initialize the TsConfing mod of the current Backend user */
        $this->userTS = $this->getBackendUser()->getTSConfig()['mod.'];

        /*Initialize variable of access from TsConfig Array*/
        $this->updateAccessByTsConfig();
        $this->filter = $filter ?? GeneralUtility::makeInstance(Filter::class);
        $this->backendSession = $backendSession ?? GeneralUtility::makeInstance(BackendSession::class);

    }

    /**
     * Init, called from parent object
     *
     * @param InfoModuleController $pObj A reference to the parent (calling) object
     */
    public function init($pObj)
    {
        $this->pObj = $pObj;
        $this->id = (int)GeneralUtility::_GP('id');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
        $this->view = $this->createView('InfoModule');
        $this->orderBy = (string)(GeneralUtility::_GP('orderBy'));
        $this->set =  GeneralUtility::_GP(self::prefix_filter . '_SET');

        // get number of items per page
        $this->groupsPerPage = $this->checkShowTsConfig('groupsPerPage');
        $this->usersPerPage = $this->checkShowTsConfig('usersPerPage');

        if($this->backendSession->get('qc_info_rights_key') != null){
            $this->filter = $this->backendSession->get('qc_info_rights_key');
        }
        else{
            // initialize the filter
            $this->updateFilter();
        }

    }
    /**
     * This function is used to manage filter and pagination
     */
    public function updateFilter(){
        $this->backendSession->store('qc_info_rights_key', $this->filter);
    }

    protected function createView(string $templateName): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:qc_info_rights/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:qc_info_rights/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:qc_info_rights/Resources/Private/Templates/Backend']);
        $view->setTemplate($templateName);
        $view->assign('pageId', $this->id);
        return $view;
    }

    /**
     * Main, called from parent object
     *
     * @return string Module content
     */
    public function main()
    {
        $this->moduleData = $this->loadModuleData();
        $this->getLanguageService()->includeLLFile('EXT:qc_info_rights/Resources/Private/Language/Module/locallang.xlf');
        if (isset($this->id)) {
            $this->modTS = BackendUtility::getPagesTSconfig($this->id)['mod.']['qcinforights.'] ?? [];
        }
        $this->initialize();
        $pageTitle = $this->pageRecord && $this->pageRecord["_thePath"] != '/' ? BackendUtility::getRecordTitle('pages', $this->pageRecord) : '';

        $this->view->assign('title', $pageTitle);
        $this->view->assign('content', $this->renderContent());
        return $this->view->render();
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

        if ($this->showTabAccess) {
            $menuItems[] = [
                'label' => $this->getLanguageService()->getLL('accessRights'),
                'content' => $this->createViewForAccessRightsTab()->render()
            ];
        }

        if ($this->showTabGroups) {
            $menuItems[] = [
                'label' => $this->getLanguageService()->getLL('beUserGroupsLists'),
                'content' => $this->createViewForBeUserGroupListTab()->render()
            ];
        }

        if ($this->showTabUsers) {
            $menuItems[] = [
                'label' => $this->getLanguageService()->getLL('beUserLists'),
                'content' => $this->createViewForBeUserListTab()->render()
            ];
        }

        return $this->moduleTemplate->getDynamicTabMenu($menuItems, 'report-qcinforights');
    }

    /**
     * Initializes the Module
     */
    protected function initialize()
    {
        $this->setPageInfo();
        $this->pageRecord = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (($this->id && is_array($this->pageRecord)) || (!$this->id && $this->getBackendUser()->isAdmin())) {
            $this->isAccessibleForCurrentUser = true;
        }
        // Don't access in workspace
        if ($this->getBackendUser()->workspace !== 0) {
            $this->isAccessibleForCurrentUser = false;
        }
        $pageRenderer = $this->moduleTemplate->getPageRenderer();
        $pageRenderer->addCssFile('EXT:qc_info_rights/Resources/Public/Css/qcinforights.css', 'stylesheet', 'all');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/QcInfoRights/ShowMembers');
        $pageRenderer->addInlineLanguageLabelFile('EXT:qc_info_rights/Resources/Private/Language/Module/locallang.xlf');
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

    /**
     * Displays the View for the Backend User List
     *
     * @return StandaloneView
     */
    protected function createViewForBeUserListTab()
    {
        $prefix = "user";
        $this->setPageInfo();

        $view = $this->createView('BeUserList');

        $demand = $this->moduleData->getDemand();
        $demand->setRejectUserStartWith('_');

        $orderArray = self::ORDER_BY_VALUES[$this->orderBy] ?? [];

        if(!empty($orderArray)){
            $this->filter->setOrderArray($orderArray);
        }

        if(!$this->showAdministratorUser){
            $demand->setUserType(Demand::USERTYPE_USERONLY);
        }

        // Filter
        if($this->set['username'] != null && !empty($this->set['username'])){
            $this->filter->setUsername($this->set['username']);
            $this->filter->setCurrentUsersTabPage(1);
        }
        if($this->set['mail'] != null && !empty($this->set['mail'])){
            $this->filter->setMail($this->set['mail']);
            $this->filter->setCurrentUsersTabPage(1);
        }
        if(!empty($this->set['hideInactif']) && (int)($this->set['hideInactif']) == 1){
            $this->filter->setHideInactiveUsers(Demand::STATUS_ACTIVE);
        }
        // Reset from form
        if($this->set['filterSearch'] == 1){
            if(empty($this->set['username'])){
                $this->filter->setUsername('');
            }
            if(empty($this->set['mail'])){
                $this->filter->setMail('');
            }
            if(empty($this->set['hideInactif'])){
                $this->filter->setHideInactiveUsers(0);
            }
            $this->filter->setCurrentUsersTabPage(1);
        }

        if (GeneralUtility::_GP('userPaginationPage') != null ){
            $userPaginationCurrentPage = (int)GeneralUtility::_GP('userPaginationPage');
            // Store the current page on session
            $this->filter->setCurrentUsersTabPage($userPaginationCurrentPage);
        }
        else{
            // read from Session
            $userPaginationCurrentPage = $this->filter->getCurrentUsersTabPage();
        }

        $this->updateFilter();
        $filterArgs = [
            'username' => $this->backendSession->get('qc_info_rights_key')->getUsername(),
            'mail' => $this->backendSession->get('qc_info_rights_key')->getMail(),
            'hideInactif' => $this->backendSession->get('qc_info_rights_key')->getHideInactiveUsers()
        ];
        $demand = $this->mapFilterToDemand($this->backendSession->get('qc_info_rights_key'));
        /**Implement tableau Header withDynamically order By Field*/
        foreach (array_keys(self::ORDER_BY_VALUES) as $key) {
            $sortActions[$key] = $this->constructBackendUri(['orderBy' => $key]);
        }
        $tabHeaders = $this->getVariablesForTableHeader($sortActions);
        $pagination = $this->getPagination($this->backendUserRepository->findDemanded($demand), $userPaginationCurrentPage,$this->usersPerPage );// we assign the groupsCurrentPaginationPage and usersCurrentPaginationPage to keep the pagination for each tab separated

        $view->assignMultiple([
            'prefix' => 'beUserList',
            'backendUsers' => $pagination['paginatedData'],
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
            'showExportUsers' => $this->showExportUsers,
            'args' => $filterArgs,
            'tabHeader' => $tabHeaders,
            'pagination' => $pagination['pagination'],
            'currentPage' => $this->id
        ]);
        return $view;
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
        $groups = $this->backendUserGroupRepository->findAll();
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
            'showMembersColumn' => $this->checkShowTsConfig('showMembersColumn')
        ]);
        return $view;
    }

    /**
     * This function is used to get the pagination items
     * @param $data
     * @param int $currentPage
     * @param int $itemsPerPage
     * @return array
     */
    public function getPagination($data, int $currentPage, int $itemsPerPage): array
    {
        // convert data to array
        $items = [];
        foreach ($data as $row) {
            array_push($items, $row);
        }
        $paginator = GeneralUtility::makeInstance(ArrayPaginator::class, $items, $currentPage, $itemsPerPage);
        $pagination = GeneralUtility::makeInstance(SimplePagination::class, $paginator);
        return [
            'paginatedData' => $paginator->getPaginatedItems(),
            'pagination' => $pagination,
            'numberOfPages' => $paginator->getNumberOfPages()
        ];
    }

    /**
     * Returns the menu array
     *
     * @return array
     */
    protected function modMenu()
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
     * @param int                    $pageId current page id
     * @param ServerRequestInterface $request
     */
    protected function fillFieldConfiguration(int $pageId, ServerRequestInterface $request)
    {
        $modTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_info.']['fieldDefinitions.'] ?? [];
        foreach ($modTSconfig as $key => $item) {
            $fieldList = str_replace('###ALL_TABLES###', $this->cleanTableNames(), $item['fields']);
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            $key = trim($key, '.');
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
                if (!$GLOBALS['TCA'][$k]['ctrl']['hideTable'] && $this->getBackendUser()->check('tables_select', $k)) {
                    $allowedTableNames['table_' . $k] = $k;
                }
            }
        }
        return implode(',', array_keys($allowedTableNames));
    }

    /*
     * This Function is used to generate Demand Model from the Backend User Model Instance
     * */
    public function loadModuleData()
    {
        $moduleData = $this->getBackendUser()->getModuleData(self::KEY) ?? '';
        if ($moduleData !== '') {
            $moduleData = @unserialize($moduleData, ['allowed_classes' => [ModuleData::class, Demand::class]]);
            if ($moduleData instanceof ModuleData) {
                return $moduleData;
            }
        }
        return GeneralUtility::makeInstance(ModuleData::class);
    }


    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Check if page record exists and set pageInfo
     */
    protected function setPageInfo(): void
    {
        $this->pageInfo = BackendUtility::readPageAccess(BackendUtility::getRecord('pages', $this->id) ? $this->id : 0, ' 1=1');
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function updateAccessByTsConfig()
    {
        $this->showTabAccess = $this->checkShowTsConfig('showTabAccess');
        $this->showTabUsers = $this->checkShowTsConfig('showTabUsers');
        $this->showTabGroups = $this->checkShowTsConfig('showTabGroups');
        $this->showExportUsers = $this->checkShowTsConfig('showExportUsers');
        $this->showExportGroups = $this->checkShowTsConfig('showExportGroups');
        $this->showAdministratorUser = $this->checkShowTsConfig('showAdministratorUser');
    }

    /**
     * This function to check if get default or Custom Value
     *
     * @param string $value
     *
     * @return string
     */
    protected function checkShowTsConfig(string $value): string
    {
        if (is_array($this->userTS['qcinforights.']) && array_key_exists($value, $this->userTS['qcinforights.'])) {
            return $this->userTS['qcinforights.'][$value];
        } else if (is_array($this->modTSconfig['properties']) && array_key_exists($value, $this->modTSconfig['properties'])) {
            return $this->modTSconfig['properties'][$value];
        }
        return '';
    }

    /**
     * This function to check if get default or Custom Value
     *
     * @param string $value
     *
     * @return string
     */
    protected function checkShowColumnTsConfig(string $value): string
    {
        if (is_array($this->userTS['qcinforights.']) && array_key_exists($value, $this->userTS['qcinforights.']['hideAccessRights.'])) {
            return $this->userTS['qcinforights.']['hideAccessRights.'][$value];
        } else if (is_array($this->modTSconfig['properties']) && array_key_exists($value, $this->modTSconfig['properties']['hideAccessRights.'])) {
            return $this->modTSconfig['properties']['hideAccessRights.'][$value];
        }
        return '';
    }

    /**
     * @param array<string,mixed> $additionalQueryParameters
     * @param string $route
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function constructBackendUri(array $additionalQueryParameters = [], string $route = 'web_info'): string
    {
        $parameters = [
            'id' => $this->id,
            'depth' => $this->depth,
            'orderBy' => $this->orderBy,
            self::prefix_filter.'_SET[username]' => $this->set['username'],
            self::prefix_filter.'_SET[mail]' => $this->set['mail'],
            self::prefix_filter.'_SET[hideInactif]' => $this->set['hideInactif'],
            self::prefix_filter.'_SET[filterSearch]' => $this->set['filterSearch'],
            'paginationPage', $this->paginationCurrentPage,
        ];

        // if same key, additionalQueryParameters should overwrite parameters
        $parameters = array_merge($parameters, $additionalQueryParameters);

        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute($route, $parameters);

        return $uri;
    }
    /**
     * Sets variables for the Fluid Template of the table with the Backend User List
     * @param array<string,string> $sortActions
     * @return mixed[] variables
     */
    protected function getVariablesForTableHeader(array $sortActions): array
    {
        $languageService = $this->getLanguageService();

        $headers = [
            'userName',
            'email',
            'lastLogin',
            'disable_compare'
        ];

        $tableHeadData = [];

        foreach ($headers as $key) {
            $tableHeadData[$key]['label'] = $languageService->sL(self::prefix_be_user_lang.$key);
            if (isset($sortActions[$key])) {
                // sorting available, add url
                if ($this->orderBy === $key) {
                    $tableHeadData[$key]['url'] = $sortActions[$key . '_reverse'] ?? '';
                } else {
                    $tableHeadData[$key]['url'] = $sortActions[$key] ?? '';
                }

                // add icon only if this is the selected sort order
                if ($this->orderBy === $key) {
                    $tableHeadData[$key]['icon'] = 'status-status-sorting-asc';
                }elseif ($this->orderBy === $key . '_reverse') {
                    $tableHeadData[$key]['icon'] = 'status-status-sorting-desc';
                }
            }
        }

        $tableHeaderHtml = [];
        foreach ($tableHeadData as $key => $values) {
            if (isset($values['url'])) {
                $tableHeaderHtml[$key]['header'] = sprintf(
                    '<a href="%s" style="text-decoration: underline;">%s</a>',
                    $values['url'],
                    $values['label']
                );
            } else {
                $tableHeaderHtml[$key]['header'] = $values['label'];
            }

            if (($values['icon'] ?? '') !== '') {
                $tableHeaderHtml[$key]['icon'] = $values['icon'];
            }
        }
        return $tableHeaderHtml;
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

    /**
     * This function is used to map the filter to Demand object
     * @param Filter $filter
     * @return Demand
     */
    public function mapFilterToDemand(Filter  $filter) : Demand {
        $demand = new \Qc\QcInfoRights\Domain\Model\Demand();
        $demand->setRejectUserStartWith($filter->getRejectUserStartWith());
        $demand->setOrderArray($filter->getOrderArray());
        $demand->setUserName($filter->getUsername());
        $demand->setEmail($filter->getMail());
        $demand->setStatus($filter->getHideInactiveUsers());
        return $demand;
    }

}
