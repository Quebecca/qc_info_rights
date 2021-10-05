<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:qc_info_rights/Configuration/TsConfig/pageconfig.tsconfig'"
);
