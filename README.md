# LibreNMS Oxidized History

LibreNMS package for viewing historical Oxidized configuration history directly from local Oxidized Git repositories.

This package adds a `Historical Config` device tab in LibreNMS. It is intended for environments where Oxidized stores configuration history in Git repositories and LibreNMS should be able to view older saved configurations without modifying LibreNMS core or Oxidized itself.

## Status

Alpha release.

This README documents the local Git history provider used by the current alpha release.

The package reads Oxidized Git repositories directly from PHP. No separate companion service, Ruby daemon, HTTP listener, or bearer token is required.

## What it does

- Adds a LibreNMS device tab named `Historical Config`
- Reads historical config versions directly from local Oxidized Git repositories
- Shows available config versions for the selected device
- Shows selected historical config content
- Shows diff output between saved versions
- Shows local backend diagnostics and detected Git repositories
- Shows installed plugin/package version
- Uses LibreNMS Oxidized group mapping where available
- Does not modify LibreNMS core
- Does not modify Oxidized

## Requirements

- LibreNMS with Composer package support
- PHP 8.2 or newer
- Git available on the LibreNMS server
- Oxidized configuration history stored in Git repositories
- The LibreNMS user must be able to read the Oxidized Git repository path

## Configuration

The package reads configuration from `config/oxidized-history.php`.

Default local Git storage root:

~~~text
/opt/librenms/.config/oxidized
~~~

Default repository mode:

~~~text
group_repos
~~~

With the default layout, the package expects repositories such as:

~~~text
/opt/librenms/.config/oxidized/cisco.git
/opt/librenms/.config/oxidized/dell.git
/opt/librenms/.config/oxidized/paloalto.git
~~~

Optional environment overrides:

~~~env
OXIDIZED_HISTORY_GIT_STORAGE_ROOT=/opt/librenms/.config/oxidized
OXIDIZED_HISTORY_GIT_REPO_MODE=group_repos
OXIDIZED_HISTORY_MAX_VERSIONS=200
OXIDIZED_HISTORY_MAX_CONFIG_BYTES=2000000
~~~

## Installation

This plugin is installed as a Composer package.

Manual copy-based installation is not the recommended installation method.

### Install from Packagist

Recommended installation method:

~~~bash
cd /opt/librenms

sudo -u librenms ./lnms plugin:add wizballesy/librenms-oxidized-history v0.1.0-alpha.7
sudo -u librenms php artisan optimize:clear
sudo -u librenms php artisan view:clear
~~~

Replace `v0.1.0-alpha.7` with the version you want to install.

After installation, open a LibreNMS device and select the `Historical Config` tab.

## Updating

To update to a specific release:

~~~bash
cd /opt/librenms

sudo -u librenms ./lnms plugin:add wizballesy/librenms-oxidized-history v0.1.0-alpha.7
sudo -u librenms php artisan optimize:clear
sudo -u librenms php artisan view:clear
~~~

Replace `v0.1.0-alpha.7` with the version you want to install.

## LibreNMS validate note

Installing LibreNMS plugin packages modifies:

~~~text
composer.json
composer.lock
~~~

LibreNMS `validate` may warn that these files are locally modified after installing or updating third-party plugin packages. This is expected because the plugin is installed as a Composer dependency inside the LibreNMS application directory.

Do not run `./scripts/github-remove` unless you intentionally want to remove local Composer changes.

## Troubleshooting

Check which backend the plugin is using:

~~~bash
cd /opt/librenms

sudo -u librenms php artisan tinker --execute='
$contract = \WizballEsy\LibreNmsOxidizedHistory\Contracts\HistoryProvider::class;
$provider = app($contract);

echo "provider=" . get_class($provider) . PHP_EOL;
'
~~~

Check that the LibreNMS user can read the Oxidized Git repositories:

~~~bash
sudo -u librenms ls -ld /opt/librenms/.config/oxidized
sudo -u librenms find /opt/librenms/.config/oxidized -maxdepth 1 -type d -name "*.git" -print
~~~

Check one repository manually:

~~~bash
sudo -u librenms git --git-dir=/opt/librenms/.config/oxidized/cisco.git log -1
~~~

## Security notes

- Keep Oxidized Git repositories readable only by trusted local users.
- Do not expose Oxidized Git repositories through the web server.
- Do not commit real device configuration data, secrets, SNMP communities, private keys, or organization-specific configuration data.
- Use normal filesystem permissions to control which local users can read historical configuration backups.

## License

GPL-3.0-or-later
