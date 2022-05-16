<?php

declare(strict_types=1);

/**
 * Definitions for routes provided by EXT:backend
 * Contains Route to Delete the Selected Exluded Link
 */
return [
    // Delete Exclude Link
    'show_members' => [
        'path' => '/show_members',
        'target' =>  Qc\QcInfoRights\Report\GroupsReport::class . '::showMembers'
    ],
];
