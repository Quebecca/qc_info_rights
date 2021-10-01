<?php
defined('TYPO3_MODE') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Qc\QcInfoRights\Report\QcInfoRightsReport;

call_user_func(static function() {
    ExtensionManagementUtility::addLLrefForTCAdescr('tx_qcinforights_domain_model_qcinforights', 'EXT:qc_info_rights/Resources/Private/Language/locallang_csh_tx_qcinforights_domain_model_qcinforights.xlf');
    ExtensionManagementUtility::allowTableOnStandardPages('tx_qcinforights_domain_model_qcinforights');
});

// Extend Module INFO with new Element
ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    QcInfoRightsReport::class,
    '',
    'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_qcInfoRight'
);

// Initialize Context Sensitive Help (CSH)
ExtensionManagementUtility::addLLrefForTCAdescr(
    'qcinforights',
    'EXT:qc_info_rights/Resources/Private/Language/Module/locallang_csh.xlf'
);
