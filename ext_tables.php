<?php
defined('TYPO3') || die();

use Qc\QcInfoRights\Report\AccessRightsReport;
use Qc\QcInfoRights\Report\GroupsReport;
use Qc\QcInfoRights\Report\UsersReport;
use TYPO3\CMS\Backend\Utility\BackendUtility;

call_user_func(static function() {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_qcinforights_domain_model_qcinforights', 'EXT:qc_info_rights/Resources/Private/Language/locallang_csh_tx_qcinforights_domain_model_qcinforights.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_qcinforights_domain_model_qcinforights');
});

/**
 * Checking if the be_user is not null to prepare his data
 */
if($GLOBALS['BE_USER'] === null) {
    $GLOBALS['BE_USER'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
    $GLOBALS['BE_USER']->start();
    $GLOBALS['BE_USER']->fetchGroupData();
}

//Render user TsConfig
$userTS = $GLOBALS['BE_USER']->getTSConfig()['mod.']['qcinforights.'];

//Rendere Page TsConfig by default get first page
$modTSconfig = BackendUtility::getPagesTSconfig(1)['mod.']['qcinforights.'];

//Checking about access
$showMenuAccess =  (int)checkShowTsConfig($userTS,$modTSconfig,'showMenuAccess');
$showMenuGroups =  (int)checkShowTsConfig($userTS,$modTSconfig,'showMenuGroups');
$showMenuUsers  =  (int)checkShowTsConfig($userTS,$modTSconfig,'showMenuUsers');


//@deprecated will removed in the next update v1.3.0
$showTabAccess =   (int)checkShowTsConfig($userTS,$modTSconfig,'showTabAccess');
$showTabGroups =   (int)checkShowTsConfig($userTS,$modTSconfig,'showTabGroups');
$showTabUsers  =    (int)checkShowTsConfig($userTS,$modTSconfig,'showTabUsers');

if($showMenuAccess || $showTabAccess) {
    // Extend Module INFO with new Element for access and rights tab
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        AccessRightsReport::class,
        '',
        'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_qcInfoRight'
    );
}

// Extend Module INFO for Groups tab
if($showTabGroups || $showMenuGroups) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        GroupsReport::class,
        '',
        'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_groups'
    );
}

// Extend Module INFO For Users tab
if($showTabUsers || $showMenuUsers){
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        UsersReport::class,
        '',
        'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_users'
    );
}

// Initialize Context Sensitive Help (CSH)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'qcinforights',
    'EXT:qc_info_rights/Resources/Private/Language/Module/locallang_csh.xlf'
);

/**
 * PHP function to check and validate the access&right for each mo
 * @param array  $userTS
 * @param array  $modTSconfig
 * @param string $value
 *
 * @return string
 */
function checkShowTsConfig(array $userTS, array $modTSconfig, string $value): string
{
    if (is_array($userTS) && array_key_exists($value, $userTS)) {
        return $userTS[$value];
    } else if (is_array($modTSconfig) && array_key_exists($value, $modTSconfig)) {
        return $modTSconfig[$value];
    }
    return '';
}