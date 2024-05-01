<?php

return [
    'dependencies' => ['core', 'backend'],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@qc/qc-info-rights/' => 'EXT:qc_info_rights/Resources/Public/JavaScript/',
    ],
];
