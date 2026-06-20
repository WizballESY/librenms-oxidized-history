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

## Installation

This plugin is installed as a Composer package.

Manual copy-based installation is not the recommended installation method.

The companion `oxidized-history-api` service must be installed and configured separately. This LibreNMS package only provides the LibreNMS UI/integration.

### Install from Packagist

Recommended installation method:

~~~bash
cd /opt/librenms

sudo -u librenms ./lnms plugin:add wizballesy/librenms-oxidized-history v0.1.0-alpha.4
sudo -u librenms php artisan optimize:clear
sudo -u librenms php artisan view:clear
~~~

This uses LibreNMS' plugin package installer and installs the package from Packagist.

After installation, open a LibreNMS device and select the `Historical Config` tab.

### LibreNMS validate note

Installing LibreNMS plugin packages modifies:

~~~text
composer.json
composer.lock
~~~

LibreNMS `validate` may warn that these files are locally modified after installing or updating third-party plugin packages. This is expected because the plugin is installed as a Composer dependency inside the LibreNMS application directory.

Do not run `./scripts/github-remove` unless you intentionally want to remove local Composer changes.

## Updating

To update to a specific release:

~~~bash
cd /opt/librenms

sudo -u librenms ./lnms plugin:add wizballesy/librenms-oxidized-history v0.1.0-alpha.4
sudo -u librenms php artisan optimize:clear
sudo -u librenms php artisan view:clear
~~~

Replace `v0.1.0-alpha.4` with the version you want to install.

## Security notes

- Do not expose the History API publicly.
- Keep the API bound to localhost or a trusted internal interface.
- Use bearer token authentication.
- Protect the token file with restrictive permissions.
- Do not commit real tokens, device secrets, SNMP communities, private keys, or organization-specific configuration data.

## License

GPL-3.0-or-later
