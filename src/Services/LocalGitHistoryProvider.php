<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Services;

use WizballEsy\LibreNmsOxidizedHistory\Contracts\HistoryProvider;

class LocalGitHistoryProvider implements HistoryProvider
{
    public function health(): array
    {
        $storageRoot = $this->storageRoot();

        if ($storageRoot === '' || ! is_dir($storageRoot) || ! is_readable($storageRoot)) {
            return [
                'ok' => false,
                'payload' => null,
                'status' => null,
                'error' => 'Oxidized Git storage root is not readable.',
            ];
        }

        $repositories = $this->discoverRepositories($storageRoot);

        return [
            'ok' => true,
            'payload' => [
                'status' => 'ok',
                'service' => 'local-git-history-provider',
                'driver' => 'local',
                'config' => [
                    'storage_root' => $storageRoot,
                    'repo_mode' => (string) config('oxidized-history.git_repo_mode', 'group_repos'),
                    'discovered_repositories' => $repositories,
                ],
            ],
            'status' => 200,
            'error' => null,
        ];
    }

    public function versions(string $nodeFull): array
    {
        return [
            'ok' => false,
            'versions' => [],
            'status' => null,
            'error' => 'Local Git versions provider is not implemented yet.',
        ];
    }

    public function versionConfig(string $nodeFull, string $oid): array
    {
        return [
            'ok' => false,
            'config' => null,
            'bytes' => null,
            'lines' => null,
            'status' => null,
            'error' => 'Local Git config provider is not implemented yet.',
        ];
    }

    public function diff(string $nodeFull, string $oidNew, string $oidOld, bool $includePatch = true): array
    {
        return [
            'ok' => false,
            'files' => [],
            'status' => null,
            'error' => 'Local Git diff provider is not implemented yet.',
        ];
    }

    private function storageRoot(): string
    {
        return rtrim((string) config('oxidized-history.git_storage_root', '/opt/librenms/.config/oxidized'), '/');
    }

    /**
     * @return array<int, string>
     */
    private function discoverRepositories(string $storageRoot): array
    {
        $repositories = [];

        foreach (glob($storageRoot . '/*.git') ?: [] as $path) {
            if (! is_dir($path) || ! is_readable($path)) {
                continue;
            }

            $name = basename($path);

            if (is_string($name) && preg_match('/\A[A-Za-z0-9._-]+\.git\z/', $name)) {
                $repositories[] = $name;
            }
        }

        sort($repositories);

        return $repositories;
    }
}
