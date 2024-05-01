<?php

namespace Qc\QcInfoRights\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qc\QcInfoRights\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class UsersInfoController
 *
 * @package \Qc\QcInfoRights\Controller
 */
class UsersInfoController extends BaseBackendController
{
    /**
     * @var BackendUserRepository
     */
    protected $backendUserRepository;

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
     * @var string Value of the GET/POST var orderBy
     */
    protected string $orderBy = '';

    /**
     * all params pass with 'prefix_user._SET'
     *
     * @var array
     */
    protected $set = [];

    /**
     * @var int
     */
    protected int  $usersPerPage = 100;

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
     * This function is used to handle requests, when no action selected
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);

        $this->set =  GeneralUtility::_GP(self::prefix_filter . '_SET');

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
        if($this->set['username'] ?? false){
            $this->filter->setUsername($this->set['username']);
            $this->filter->setCurrentUsersTabPage(1);
        }
        if($this->set['mail'] ?? false){
            $this->filter->setMail($this->set['mail']);
            $this->filter->setCurrentUsersTabPage(1);
        }
        if(($this->set['hideInactif'] ?? -1) == 1){
            $this->filter->setHideInactiveUsers(Demand::STATUS_ACTIVE);
        }

        // Reset from form
        if(($this->set['filterSearch'] ?? -1) == 1){
            if(empty($this->set['username'] ?? '')){
                $this->filter->setUsername('');
            }
            if(empty($this->set['mail'] ?? '')){
                $this->filter->setMail('');
            }
            if(empty($this->set['hideInactif'] ?? 0)){
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
        $filterArgs = [];

        if(!is_null($this->backendSession->get('qc_info_rights_key'))){
            $filterArgs = [
                'username' => $this->backendSession->get('qc_info_rights_key')->getUsername(),
                'mail' => $this->backendSession->get('qc_info_rights_key')->getMail(),
                'hideInactif' => $this->backendSession->get('qc_info_rights_key')->getHideInactiveUsers()
            ];
            $demand = $this->mapFilterToDemand($this->backendSession->get('qc_info_rights_key'));
        }

        /**Implement tableau Header withDynamically order By Field*/
        $sortActions = [];
        foreach (array_keys(self::ORDER_BY_VALUES) as $key) {
            $sortActions[$key] = $this->constructBackendUri(['orderBy' => $key]);
        }
        $tabHeaders = $this->getVariablesForTableHeader($sortActions);
        //$pagination = $this->getPagination($this->backendUserRepository->findDemanded($demand), $userPaginationCurrentPage,$this->usersPerPage );// we assign the groupsCurrentPaginationPage and usersCurrentPaginationPage to keep the pagination for each tab separated

        $itemUserInfo = $this->backendUserRepository->findDemanded($demand);

        $paginator = new QueryResultPaginator($itemUserInfo, $request->getQueryParams()['currentPage'] ?? 1, $this->usersPerPage);
        $pagination = new SimplePagination($paginator);

        foreach ($paginator->getPaginatedItems() as $item){
            $crdate = BackendUtility::getRecord	('be_users', $item->getUid(), 'crdate', 'true')['crdate'];
            $item->setCrdate($crdate);
        }

        $this->view->assignMultiple([
            'prefix' => 'beUserList',
            'paginator' => $paginator,
            'pagination' => $pagination,
            'backendUsers' => $paginator->getPaginatedItems(),
            'showExportUsers' => $this->showExportUsers,
            'args' => $filterArgs,
            'tabHeader' => $tabHeaders,


            'currentPage' => $this->id
        ]);

        return $this->view->renderResponse('UsersInfo');
    }

    /**
     * This function is used to construct the backend uri
     * @param array<string,mixed> $additionalQueryParameters
     * @return string
     * @throws RouteNotFoundException
     */
    protected function constructBackendUri(array $additionalQueryParameters = [], string $route = 'web_info'): string
    {
        $parameters = [
            'id' => $this->id,
            'orderBy' => $this->orderBy,
            self::prefix_filter.'_SET[username]' => $this->set['username'] ?? null,
            self::prefix_filter.'_SET[mail]' => $this->set['mail'] ?? null,
            self::prefix_filter.'_SET[hideInactif]' => $this->set['hideInactif'] ?? null,
            self::prefix_filter.'_SET[filterSearch]' => $this->set['filterSearch'] ?? null,
        ];

        // if same key, additionalQueryParameters should overwrite parameters
        $parameters = [...$parameters, ...$additionalQueryParameters];

        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string) $uriBuilder->buildUriFromRoute($route, $parameters);
    }

    /**
     * Sets variables for the Fluid Template of the table with the Backend User List
     * @param array<string,string> $sortActions
     * @return array variables
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
                $tableHeadData[$key]['url'] =
                    $this->orderBy === $key
                        ? $sortActions[$key . '_reverse'] ?? ''
                        : $sortActions[$key] ?? ''
                ;

                // add icon only if this is the selected sort order
                if ($this->orderBy === $key) {
                    'status-status-sorting-asc';
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

    /**
     * This Action is to export Backend user as a CSV Files
     *
     * @return ResponseInterface
     */
    public function exportBackendUserListAction(ServerRequestInterface $request): ResponseInterface
    {
        //Initialize Response and create Name of Our FIle CSV
        $format = 'csv';
        $title = 'Backend-user-list-Export-' . date('Y-m-d_H-i');
        $filename = $title . '.' . $format;

        $response = new Response('php://output', 200,
            ['Content-Type' => 'text/csv; charset=utf-8',
                'Content-Description' => 'File transfer',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );

        //Implement Array Contains Key of the Lang File To regenerate an Array For CSV Header
        $LangArrayHeader = [
            'csvHeader.uid',
            'csvHeader.userName',
            'csvHeader.fullName',
            'csvHeader.mail',
            'csvHeader.lastLogin',
            'csvHeader.isHidden',
            'csvHeader.isAdmin',
            'csvHeader.crdate'
        ];

        //CSV HEADERS Using Translate File and respecting UTF-8 Charset for Special Char
        $headerCsv = $this->generateCsvHeaderArray($LangArrayHeader);

        $demand = $this->loadModuleData()->getDemand();
        $demand->setRejectUserStartWith('_');

        if(!$this->showAdministratorUser){
            $demand->setUserType(Demand::USERTYPE_USERONLY);
        }

        /**Verification of Filter Element*/
        $UriParam= $request->getQueryParams();

        /**Check if exist Params to execute Instruction*/
        if(is_array($UriParam)){
            //Filter for user name
            if(!empty($UriParam['username'])){
                $demand->setUserName($UriParam['username']);
            }

            //Filter for address mail
            if(!empty($UriParam['mail'])){
                $demand->setEmail($UriParam['mail']);
            }

            //Filter if user want to hide inactive User
            if(!empty($UriParam['hideInactif']) && (int)($UriParam['hideInactif']) == 1){
                $demand->setStatus(Demand::STATUS_ACTIVE);
            }
        }

        //Render All Backend User
        $beUser = $this->backendUserRepository->findDemanded($demand);

        //Open File Based on Function Php To start Write inside the file CSV
        $fp = fopen('php://output', 'wb');

        fputcsv($fp, $headerCsv, $this->delimiter, $this->quote);

        foreach ($beUser as $item) {
            //Get TimeStamp of last Login and convert to custom Format
            $LastLogin = $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'never');
            $format = $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'dateFormat');

            if (!is_null($item->getLastLoginDateAndTime())) {
                $timeStamp = $item->getLastLoginDateAndTime()->getTimestamp();
                $LastLogin = date($format, $timeStamp);
            }

            $crdate = BackendUtility::getRecord('be_users', $item->getUid(), 'crdate', 'true')['crdate'];
            $formattedCrDate = date($format, intval($crdate));

            //Fill Array of User by Data
            $arrayData = [];
            $arrayData[] = $item->getUid();
            $arrayData[] = $this->charsetConverter->conv($item->getUserName(), 'utf-8', 'iso-8859-15');
            $arrayData[] = $this->charsetConverter->conv($item->getRealName(), 'utf-8', 'iso-8859-15');
            $arrayData[] = $this->charsetConverter->conv($item->getEmail(), 'utf-8', 'iso-8859-15');
            $arrayData[] = $LastLogin;
            $arrayData[] = $item->getIsDisabled() ? $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'yes') : $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'no');
            $arrayData[] = $item->getIsAdministrator() ? $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'yes') : $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'no');
            $arrayData[] = $formattedCrDate;

            //Write Inside Our CSV File
            fputcsv($fp, $arrayData, $this->delimiter, $this->quote);
        }
        fclose($fp);
        return $response;
    }
}
