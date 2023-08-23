<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:qc_info_rights/Configuration/TsConfig/pageconfig.tsconfig'"
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Info\Controller\InfoModuleController::class] = ['className' => \Qc\QcInfoRights\Controller\QcInfoModuleController::class];
