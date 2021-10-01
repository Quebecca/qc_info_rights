<?php
declare(strict_types=1);
use Qc\QcInfoRights\Controller;

/**
 * Definitions for routes provided by EXT:backend
 * Contains Route to Export Lists of backend User
 */
return [
    //Backend Route link To Export Backend user list
    'export_be_user_list' => [
        'path' => '/export-be-user',
        'referrer' => 'required,refresh-empty',
        'target' =>  Controller\BackendController::class . '::exportBackendUserListAction'
    ],

    //Backend Route link To Export Backend Group user list
    'export_be_user_group_list' => [
        'path' => '/export-be-user-group',
        'referrer' => 'required,refresh-empty',
        'target' =>  Controller\BackendController::class . '::exportBackendUserGroupListAction'
    ],
];
