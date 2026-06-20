<?php

return [
    'driver' => env('OXIDIZED_HISTORY_DRIVER', 'api'),

    'api_url' => env('OXIDIZED_HISTORY_API_URL', 'http://127.0.0.1:8899'),
    'api_timeout' => env('OXIDIZED_HISTORY_API_TIMEOUT', 2.0),
    'api_token' => env('OXIDIZED_HISTORY_API_TOKEN'),
    'api_token_file' => env('OXIDIZED_HISTORY_API_TOKEN_FILE', '/etc/oxidized-history-api/token'),

    'git_storage_root' => env('OXIDIZED_HISTORY_GIT_STORAGE_ROOT', '/opt/librenms/.config/oxidized'),
    'git_repo_mode' => env('OXIDIZED_HISTORY_GIT_REPO_MODE', 'group_repos'),
    'git_groups' => [],

    'max_versions' => env('OXIDIZED_HISTORY_MAX_VERSIONS', 200),
    'max_config_bytes' => env('OXIDIZED_HISTORY_MAX_CONFIG_BYTES', 2000000),

    'visibility_mode' => env('OXIDIZED_HISTORY_VISIBILITY_MODE', 'always'),

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
