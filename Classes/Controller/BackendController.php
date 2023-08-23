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
namespace Qc\QcInfoRights\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qc\QcInfoRights\Domain\Model\ModuleData;
use Qc\QcInfoRights\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class BackendController
 *
 * @package \Qc\QcInfoRights\Controller
 */
class BackendController
{
    /**
     * @var string
     */
    final public const KEY = 'tx_beuser';

    /**
     * @var string
     */
    final public const MODULE_LANG_FILE = "LLL:EXT:qc_info_rights/Resources/Private/Language/Module/locallang.xlf:";

    /**
     * TSconfig of the current module
     *
     * @var array
     */
    protected $modTSconfig = [];

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
     * @var boolean
     */
    protected $showAdministratorUser;


    /**
     * BackendController constructor.
     *
     * @param \TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository|null $backendUserGroupRepository
     * @param \TYPO3\CMS\Extbase\Utility\LocalizationUtility|null                 $localizationUtility
     * @param \TYPO3\CMS\Core\Charset\CharsetConverter|null                       $charsetConverter
     */
    public function __construct(
        BackendUserGroupRepository $backendUserGroupRepository = null,
        LocalizationUtility $localizationUtility = null,
        CharsetConverter $charsetConverter = null
    )
    {
        //Initialize Repository
        $this->backendUserGroupRepository = $backendUserGroupRepository ?? GeneralUtility::makeInstance(BackendUserGroupRepository::class);
        $this->localizationUtility = $localizationUtility ?? GeneralUtility::makeInstance(LocalizationUtility::class);
        $this->charsetConverter = $charsetConverter ?? GeneralUtility::makeInstance(CharsetConverter::class);

        //Initialize Repository Backend user
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $this->backendUserRepository = GeneralUtility::makeInstance(BackendUserRepository::class, $this->objectManager);
        $this->backendUserRepository->injectPersistenceManager($persistenceManager);

        //Render configuration from ext_conf_template file for quote and delimter
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $configuration = $extensionConfiguration->get('qc_info_rights');
        if (is_array($configuration)) {
            $this->quote = $configuration['quote'];
            $this->delimiter = $configuration['delimiter'];
        }

        //Initialize the TsConfig from User and Extension
        $this->initializeTsConfig();

        //Check if Administrator Should be Visible or not
        $this->showAdministratorUser = $this->checkShowTsConfig('showAdministratorUser');
    }

    /**
     * This Action is to export Backend user as a CSV Files
     *
     * @return \Psr\Http\Message\ResponseInterface
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

        $demand = $this->moduleData = $this->loadModuleData()->getDemand();

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

    /**
     * @return ResponseInterface
     */
    public function exportBackendUserGroupListAction(): ResponseInterface
    {
        //Initialize Response and prepare the CSV output file
        $format = 'csv';
        $title = 'Backend-user-group-list-Export-' . date('Y-m-d_H-i');
        $filename = $title . '.' . $format;

        $response = new Response('php://output', 200,
            ['Content-Type' => 'text/csv; charset=utf-8',
                'Content-Description' => 'File transfer',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );

        //Implement Array Contains Key of the Lang File To regenerate an Array For CSV Header
        $LangArrayHeader = ['csvHeader.uid', 'csvHeader.title', 'csvHeader.description', 'csvHeader.isHidden'];

        //CSV HEADERS Using Translate File and respecting UTF-8 Charset for Special Char
        $headerCsv = $this->generateCsvHeaderArray($LangArrayHeader);

        //Render Fill list of Backend User Group
        $beUserGroup = $this->backendUserGroupRepository->findAll();

        //Open File Based on Function Php To start Write inside the file CSV
        $fp = fopen('php://output', 'wb');

        fputcsv($fp, $headerCsv, $this->delimiter, $this->quote);

        foreach ($beUserGroup as $key => $item) {
            //Fill Array of User by Data
            $arrayData = [];
            $arrayData[] = $item->getUid();
            $arrayData[] = $this->charsetConverter->conv($item->getTitle(), 'utf-8', 'iso-8859-15');
            $arrayData[] = $this->charsetConverter->conv($item->getDescription(), 'utf-8', 'iso-8859-15');
            $arrayData[] = $item->getHidden() ? $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'yes') : $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'no');
            //Write Inside Our CSV File
            fputcsv($fp, $arrayData, $this->delimiter, $this->quote);
        }
        fclose($fp);

        return $response;
    }

    /**
     * This Function to Generate an array for Header CSV Based on Language file get as parameter and array of key of language file "LLL:EXT:qc_info_rights/Resources/Private/Language/Module/locallang.xlf"
     *
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function initializeTsConfig(){
        $this->userTS = $this->getBackendUser()->getTSConfig()['mod.'];
        /*Initialize the TsConfig Array*/
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['qcinforights.'] ?? [];
    }

    /**
     * This function to check if get default or Custom Value
     *
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

}
