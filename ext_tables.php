<?php
defined('TYPO3_MODE') || die();

use Qc\QcInfoRights\Report\AccessRightsReport;
use Qc\QcInfoRights\Report\GroupsReport;
use Qc\QcInfoRights\Report\QcInfoRightsReport;
use Qc\QcInfoRights\Report\UsersReport;

call_user_func(static function() {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_qcinforights_domain_model_qcinforights', 'EXT:qc_info_rights/Resources/Private/Language/locallang_csh_tx_qcinforights_domain_model_qcinforights.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_qcinforights_domain_model_qcinforights');
});

// Extend Module INFO with new Element
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    AccessRightsReport::class,
    '',
    'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_qcInfoRight'
);

// Extend Module INFO with new Element
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    GroupsReport::class,
    '',
    'Groups'
);

// Extend Module INFO with new Element
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    UsersReport::class,
    '',
    'Users'
);



// Initialize Context Sensitive Help (CSH)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'qcinforights',
    'EXT:qc_info_rights/Resources/Private/Language/Module/locallang_csh.xlf'
);
