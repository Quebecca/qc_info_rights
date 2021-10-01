<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:qc_info_rights/Configuration/TsConfig/pageconfig.tsconfig'"
);

/**
 * Extend Backend User Repository
 */
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('beuser')) {
    /*$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Beuser\\Domain\\Repository\\BackendUserRepository'] = array(
        'className' => 'Qc\\QcInfoRights\Domain\\Repository\\BackendUserRepository'
    );*/
}
