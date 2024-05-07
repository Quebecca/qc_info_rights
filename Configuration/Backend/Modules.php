<?php

use Qc\QcInfoRights\Controller\GroupsController;
use Qc\QcInfoRights\Controller\AccessRightsInfoController;
use Qc\QcInfoRights\Controller\UsersInfoController;
use Qc\QcInfoRights\Controller\BaseBackendController;

return [
    'web_qcInfoRightsQcInfoRightsbe' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'iconIdentifier' => 'qc-info-right-backend-module-icon',
        'path' => '/module/web/qcInfoRightsQcInfoRightsbe',
        'labels' => [
            'title' => 'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:module_title',
        ],        'extensionName' => 'QcInfoRights',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => BaseBackendController::class . '::handleRequests',
            ],
        ],
    ],
    'web_qcInfoRightsQcInfoRightsbe_access' => [
        'parent' => 'web_qcInfoRightsQcInfoRightsbe',
        'access' => 'user',
        'path' => '/module/web/qcInfoRightsQcInfoRightsbeAccss',
        'iconIdentifier' => 'qc-info-right-backend-module-icon',
        'labels' => [
            'title' => 'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_qcInfoRight',
        ],
        'routes' => [
            '_default' => [
                'target' => AccessRightsInfoController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'depth' => 0,
        ],
    ],
    'web_qcInfoRightsQcInfoRightsbe_groups' => [
        'parent' => 'web_qcInfoRightsQcInfoRightsbe',
        'access' => 'user',
        'path' => '/module/web/web_qcInfoRightsQcInfoRightsbe_groups',
        'iconIdentifier' => 'qc-info-right-backend-module-icon',
        'labels' => [
            'title' => 'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_groups',
        ],
        'routes' => [
            '_default' => [
                'target' => GroupsController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'depth' => 0,
        ],
    ],
    'web_qcInfoRightsQcInfoRightsbe_users' => [
        'parent' => 'web_qcInfoRightsQcInfoRightsbe',
        'access' => 'user',
        'path' => '/module/web/web_qcInfoRightsQcInfoRightsbe_users',
        'iconIdentifier' => 'qc-info-right-backend-module-icon',
        'labels' => [
            'title' => 'LLL:EXT:qc_info_rights/Resources/Private/Language/locallang.xlf:mod_users',
        ],
        'routes' => [
            '_default' => [
                'target' => UsersInfoController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'depth' => 0,
        ],
    ],
];
