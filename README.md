# LibreNMS Oxidized History

LibreNMS package for viewing historical Oxidized configuration history through an external Oxidized History API service.

This package adds a `Historical Config` device tab in LibreNMS. It is intended for environments where Oxidized stores configuration history in Git repositories, and LibreNMS should be able to view older saved configurations without modifying LibreNMS core or Oxidized itself.

## Status

Alpha release.

Current package release:

~~~text
v0.1.0-alpha.4
~~~

The companion History API service is installed separately. The LibreNMS package does not run or install the API service automatically.

## What it does

- Adds a LibreNMS device tab named `Historical Config`
- Reads historical config versions from an external History API
- Shows available config versions for the selected device
- Shows selected historical config content
- Shows diff output where available
- Shows History API status and version
- Shows installed plugin/package version
- Uses LibreNMS Oxidized group mapping where available
- Does not modify LibreNMS core
- Does not modify Oxidized

## Components

This project has two parts:

1. `wizballesy/librenms-oxidized-history`  
   The LibreNMS Composer package / plugin UI.

2. `oxidized-history-api`  
   A separate service that reads Oxidized Git repositories and exposes a local HTTP API.

The API service should normally listen locally, for example:

~~~text
http://127.0.0.1:8899
~~~

## Requirements

- LibreNMS with Composer package support
- PHP 8.2 or newer
- Oxidized configuration history stored in Git repositories
- The separate `oxidized-history-api` service installed and reachable from LibreNMS
- Bearer token authentication recommended for the API service

## Configuration

The package reads configuration from `config/oxidized-history.php`.

Default API URL:

~~~text
http://127.0.0.1:8899
~~~

Default token file:

~~~text
/etc/oxidized-history-api/token
~~~

The token file should be readable by the LibreNMS user and should not be committed to Git.

Example environment overrides:

~~~env
OXIDIZED_HISTORY_API_URL=http://127.0.0.1:8899
OXIDIZED_HISTORY_API_TOKEN_FILE=/etc/oxidized-history-api/token
~~~

## Local development model

For local development, use a Composer path repository with symlink enabled:

~~~text
/opt/librenms/local-packages/librenms-oxidized-history
  = active Git working tree

/opt/librenms/vendor/wizballesy/librenms-oxidized-history
  -> ../../local-packages/librenms-oxidized-history/
~~~

Do not manually edit or rsync files into `vendor/`.

## Normal install model

The intended normal install model is GitHub + Packagist, similar to other Composer-based LibreNMS packages.

Once published, normal installs should use Composer package versions instead of a local path repository.

## Security notes

- Do not expose the History API publicly.
- Keep the API bound to localhost or a trusted internal interface.
- Use bearer token authentication.
- Protect the token file with restrictive permissions.
- Do not commit real tokens, device secrets, SNMP communities, private keys, or organization-specific configuration data.

## License

GPL-3.0-or-later
