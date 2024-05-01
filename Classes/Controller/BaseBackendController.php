<?php

namespace Qc\QcInfoRights\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qc\QcInfoRights\BackendSession\BackendSession;
use Qc\QcInfoRights\Domain\Model\ModuleData;
use Qc\QcInfoRights\Domain\Repository\BackendUserRepository;
use Qc\QcInfoRights\Filter\Filter;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Class BaseBackendController
 *
 * @package \Qc\QcInfoRights\Controller
 */
class BaseBackendController
{

    /**
     * @var string
     */
    final public const MODULE_LANG_FILE = "LLL:EXT:qc_info_rights/Resources/Private/Language/Module/locallang.xlf:";

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
     * @var int
     */
    protected int  $groupsPerPage = 100;

    /**
     * @var BackendUserRepository
     */
    protected $backendUserRepository;

    /**
     * @var BackendUserGroupRepository
     */
    protected $backendUserGroupRepository;

    /**
     * @var CharsetConverter
     */
    protected $charsetConverter;

    /**
     * @var LocalizationUtility
     */
    protected $localizationUtility;

    /**
     * TSconfig of the current User Backend
     *
     * @var array
     */
    protected $userTS = [];

    /**
     * @var string
     */
    protected $quote;

    /**
     * @var string
     */
    protected $delimiter;

    /**
     * TSconfig of the current module
     *
     * @var array
     */
    protected array $modTS = [];

    /**
     * @var ModuleTemplate
     */
    protected ModuleTemplate $moduleTemplate;

    /**
     * @var ModuleTemplateFactory
     */
    protected ModuleTemplateFactory $moduleTemplateFactory;

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
     * @var BackendSession
     */
    protected BackendSession $backendSession;

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    protected ModuleInterface $currentModule;

    protected ?ModuleTemplate $view;

    public array $pageinfo = [];

    /**
     * @var string
     */
    protected string $currentAction = '';

    /**
     * @var string
     */
    public const KEY = 'tx_beuser';

    /**
     * @var ModuleData
     */
    protected ModuleData $moduleData;

    /**
     * @param ModuleTemplateFactory $moduleTemplateFactory
     *
     * @return void
     */
    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * @param \TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository|null $backendUserGroupRepository
     * @param \TYPO3\CMS\Extbase\Utility\LocalizationUtility|null                 $localizationUtility
     * @param \TYPO3\CMS\Core\Charset\CharsetConverter|null                       $charsetConverter
     *
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(
        BackendUserGroupRepository $backendUserGroupRepository = null,
        LocalizationUtility $localizationUtility = null,
        CharsetConverter $charsetConverter = null
    )
    {
        $this->moduleData = $this->loadModuleData();
        /*Initialize the TsConfig Array*/
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['qcinforights.'] ?? [];
        //Initialize Repository
        $this->backendUserGroupRepository = $backendUserGroupRepository ?? GeneralUtility::makeInstance(BackendUserGroupRepository::class);
        $this->localizationUtility = $localizationUtility ?? GeneralUtility::makeInstance(LocalizationUtility::class);
        $this->charsetConverter = $charsetConverter ?? GeneralUtility::makeInstance(CharsetConverter::class);

        //Initialize Repository Backend user
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $this->backendUserRepository = GeneralUtility::makeInstance(BackendUserRepository::class);
        $this->backendUserRepository->injectPersistenceManager($persistenceManager);

        //Render configuration from ext_conf_template file for quote and delimter
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $configuration = $extensionConfiguration->get('qc_info_rights');
        if (is_array($configuration)) {
            $this->quote = $configuration['quote'];
            $this->delimiter = $configuration['delimiter'];
        }

        $this->backendSession = GeneralUtility::makeInstance(BackendSession::class);

        /*Initialize the TsConfing mod of the current Backend user */
        $this->userTS = $this->getBackendUser()->getTSConfig()['mod.'];

        /*Initialize variable of access from TsConfig Array*/
        $this->updateAccessByTsConfig();

    }

    protected function init(ServerRequestInterface $request): void
    {
        $this->id = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->currentModule = $request->getAttribute('module');
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];

        $this->filter = GeneralUtility::makeInstance(Filter::class);

        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $this->view->setTitle(
            $this->getLanguageService()->sL($this->currentModule->getTitle()),
            $this->id !== 0 && isset($this->pageinfo['title']) ? $this->pageinfo['title'] : ''
        );

        if ($this->pageinfo !== []) {
            $this->view->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }

        $this->view->assign('id', $this->id);
        $this->view->makeDocHeaderModuleMenu(['id' => $this->id]);
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        return $this->view->renderResponse('Main');
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
     * @param string $value
     *
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
     * @param \Qc\QcInfoRights\Filter\Filter $filter
     *
     * @return \TYPO3\CMS\Beuser\Domain\Model\Demand
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

    /**
     * This function is used to get the pagination items
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $data
     * @param int                                                 $currentPage
     * @param int                                                 $itemsPerPage
     *
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
     *  This Function to Generate an array for Header CSV Based on Language file get as parameter and array of key of language file "LLL:EXT:qc_info_rights/Resources/Private/Language/Module/locallang.xlf"*
     * @param array $itemsArray
     *
     * @return array
     */
    protected function generateCsvHeaderArray(array $itemsArray): array
    {
        $headerCsv = [];
        for ($i = 0; $i < count($itemsArray); $i++) {
            $headerCsv[] = $this->charsetConverter->conv($this->localizationUtility->translate(Self::MODULE_LANG_FILE . $itemsArray[$i]), 'utf-8', 'iso-8859-15');
        }
        return $headerCsv;
    }

    /**
     * This function is used to manage filter and pagination
     */
    public function updateFilter(){
        $this->backendSession->store('qc_info_rights_key', $this->filter);
    }

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
}
