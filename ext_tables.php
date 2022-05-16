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

$modTSconfig = BackendUtility::getPagesTSconfig(1)['mod.']['qcinforights.'];

if($modTSconfig['showTabAccess'] == 1 && $modTSconfig['showMenuAccess']) {
    // Extend Module INFO with new Element for access and rights tab
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        AccessRightsReport::class,
        '',
        'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_qcInfoRight'
    );
}

// Extend Module INFO for Groups tab
if($modTSconfig['showTabGroups'] == 1 && $modTSconfig['showMenuGroups'] == 1) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        GroupsReport::class,
        '',
        'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_groups'
    );
}

if($modTSconfig['showTabUsers'] == 1 && $modTSconfig['showMenuUsers'] == 1){
// Extend Module INFO For Users tab
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
