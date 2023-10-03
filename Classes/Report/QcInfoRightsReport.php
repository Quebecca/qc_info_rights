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

use TYPO3\CMS\Core\Page\PageRenderer;
use Qc\QcInfoRights\BackendSession\BackendSession;
use Qc\QcInfoRights\Domain\Model\ModuleData;
use Qc\QcInfoRights\Filter\Filter;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Module 'Qc info rights' as sub module of Web -> Info
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
abstract class QcInfoRightsReport
{
    /**
     * @var string
     */
    public const KEY = 'tx_beuser';

    /**
     * @var string
     */
    public const prefix_be_user_lang = 'LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:';

    /**
     * @var string
     */
    public const prefix_core_lang = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:';

    /***
     * @var  string
     */
    public const prefix_filter = 'user';

    /**
     * @var ModuleData
     */
    protected ModuleData $moduleData;

    /**
     * Information about the current page record
     *
     * @var array
     */
    protected array $pageRecord = [];

    /**
     * Information, if the module is accessible for the current user or not
     *
     * @var bool
     */
    protected bool $isAccessibleForCurrentUser = false;

    /**
     * TSconfig of the current module
     *
     * @var array
     */
    protected array $modTS = [];

    /**
     * TSconfig of the current User Backend
     *
     * @var array
     */
    protected $userTS = [];

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected InfoModuleController $pObj;

    /**
     * @var ModuleTemplate
     */
    protected ModuleTemplate $moduleTemplate;

    /**
     * @var StandaloneView
     */
    protected StandaloneView $view;

    /**
     * @var boolean
     */
    protected bool $showExportUsers;

    /**
     * @var boolean
     */
    protected bool $showExportGroups;

    /**
     * @var boolean
     */
    protected bool $showAdministratorUser;

    /**
     * Module TSconfig based on PAGE TSconfig / USER TSconfig
     *
     * @var array
     */
    protected array $modTSconfig;

    /**
     * @var Filter
     */
    protected Filter $filter;

    /**
     * @var BackendSession
     */
    protected BackendSession $backendSession;

    /**
     * @var Icon
     */
    protected $icon;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var int
     */
    protected int $typoVersion;

    /**
     * QcInfoRightsReport constructor.
     *
     */
    public function __construct(private PageRenderer $pageRenderer)
    {
        /*Initialize the TsConfig Array*/
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['qcinforights.'] ?? [];

        /*Initialize the TsConfing mod of the current Backend user */
        $this->userTS = $this->getBackendUser()->getTSConfig()['mod.'];

        /*Initialize variable of access from TsConfig Array*/
        $this->updateAccessByTsConfig();
        $this->filter = GeneralUtility::makeInstance(Filter::class);
        $this->backendSession = GeneralUtility::makeInstance(BackendSession::class);
        $this->typoVersion = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->icon = $this->iconFactory->getIcon('actions-document-export-csv', Icon::SIZE_SMALL);
    }

    /**
     * Init, called from parent object
     *
     * @param InfoModuleController $pObj A reference to the parent (calling) object
     */
    public function init(InfoModuleController $pObj)
    {
        $this->id = (int)GeneralUtility::_GP('id');
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->view = $this->createView('InfoModule');
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

    /**
     * @return StandaloneView
     */
    protected function createView(string $templateName): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:qc_info_rights/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:qc_info_rights/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:qc_info_rights/Resources/Private/Templates/Backend']);
        $view->setTemplate($templateName);

        $view->assignMultiple([
            'pageId' => $this->id,
            'icon' => $this->icon,
            'typoVersion' =>  $this->typoVersion
        ]);
        return $view;
    }

    /**
     * Main, called from parent object
     *
     * @return string Module content
     */
    public function main(): string
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
     * @return string
     */
    protected abstract function renderContent(): string;

    /**
     * Initializes the Module
     */
    protected function initialize()
    {
        $this->pageRecord = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (($this->id && is_array($this->pageRecord)) || (!$this->id && $this->getBackendUser()->isAdmin())) {
            $this->isAccessibleForCurrentUser = true;
        }
        // Don't access in workspace
        if ($this->getBackendUser()->workspace !== 0) {
            $this->isAccessibleForCurrentUser = false;
        }
        $pageRenderer = $this->pageRenderer;
        $pageRenderer->addCssFile('EXT:qc_info_rights/Resources/Public/Css/qcinforights.css', 'stylesheet', 'all');
        $pageRenderer->addInlineLanguageLabelFile('EXT:qc_info_rights/Resources/Private/Language/Module/locallang.xlf');
    }

    /**
     * This function is used to get the pagination items
     * @return array
     */
    public function getPagination(QueryResultInterface $data, int $currentPage, int $itemsPerPage): array
    {
        // convert data to array
        $items = [];
        foreach ($data as $row) {
            $items[] = $row;
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
     * @return ModuleData
     * This Function is used to generate Demand Model from the Backend User Model Instance
     */
    public function loadModuleData(): ModuleData
    {
        $moduleData = $this->getBackendUser()->getModuleData(self::KEY) ?? '';
        if ($moduleData !== '' && is_string($moduleData)) {
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * This function is used to access to the ts config options
     */
    protected function updateAccessByTsConfig()
    {
        $this->showExportUsers = (boolean) $this->checkShowTsConfig('showExportUsers');
        $this->showExportGroups = (boolean) $this->checkShowTsConfig('showExportGroups');
        $this->showAdministratorUser = (boolean) $this->checkShowTsConfig('showAdministratorUser');
    }

    /**
     * This function to check if get default or Custom Value
     * @return string
     */
    protected function checkShowTsConfig(string $value): string
    {
        if (is_array($this->userTS['qcinforights.'] ?? null)
            && array_key_exists($value, $this->userTS['qcinforights.'] ?? [])) {
            return $this->userTS['qcinforights.'][$value];
        } else if (is_array($this->modTSconfig['properties']) && array_key_exists($value, $this->modTSconfig['properties'])) {
            return $this->modTSconfig['properties'][$value];
        }
        return '';
    }

    /**
     * This function is used to map the filter to Demand object
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
