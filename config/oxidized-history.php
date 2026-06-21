<?php

return [

    'git_storage_root' => env('OXIDIZED_HISTORY_GIT_STORAGE_ROOT', '/opt/librenms/.config/oxidized'),
    'git_repo_mode' => env('OXIDIZED_HISTORY_GIT_REPO_MODE', 'group_repos'),
    'git_groups' => [],

    'max_versions' => env('OXIDIZED_HISTORY_MAX_VERSIONS', 200),
    'max_config_bytes' => env('OXIDIZED_HISTORY_MAX_CONFIG_BYTES', 2000000),

    'visibility_mode' => env('OXIDIZED_HISTORY_VISIBILITY_MODE', 'always'),

    // Fallback only. OxidizedNodeResolver uses LibreNMS oxidized.maps.group first.
    // This map is used when LibreNMS has no matching Oxidized group mapping.
    'group_os_map' => [
        'ios' => 'cisco',
        'iosxe' => 'cisco',
        'cisco' => 'cisco',
        'dell' => 'dell',
        'powerconnect' => 'dell',
        'paloalto' => 'paloalto',
        'panos' => 'paloalto',
    ],

    'default_group' => null,
];
