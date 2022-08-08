<?php

use Qc\QcInfoRights\Report\AccessRightsReport;
use Qc\QcInfoRights\Report\GroupsReport;
use Qc\QcInfoRights\Report\UsersReport;

defined('TYPO3') || die();

call_user_func(static function() {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_qcinforights_domain_model_qcinforights', 'EXT:qc_info_rights/Resources/Private/Language/locallang_csh_tx_qcinforights_domain_model_qcinforights.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_qcinforights_domain_model_qcinforights');
});

// Initialize Context Sensitive Help (CSH)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'qcinforights',
    'EXT:qc_info_rights/Resources/Private/Language/Module/locallang_csh.xlf'
);

//Add the Group tab to the Menu to the Menu Contextual
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    GroupsReport::class,
    '',
    'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_groups'
);

//Add the Access right tab to the Menu Contextual
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    AccessRightsReport::class,
    '',
    'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_qcInfoRight'
);

//Add the Users tab to the Menu to the Menu Contextual
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    UsersReport::class,
    '',
    'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_users'
);