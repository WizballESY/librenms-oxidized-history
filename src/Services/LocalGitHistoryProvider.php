<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Services;

use Symfony\Component\Process\Process;
use Throwable;
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
        [$group, $node] = $this->parseNodeFull($nodeFull);

        if (! $this->safeSegment($node)) {
            return [
                'ok' => false,
                'versions' => [],
                'status' => 400,
                'error' => 'invalid node_full/node',
            ];
        }

        if (! $this->safeSegment($group)) {
            return [
                'ok' => false,
                'versions' => [],
                'status' => 400,
                'error' => 'group is required',
            ];
        }

        $repoPath = $this->repoPathForGroup((string) $group);

        if ($repoPath === null) {
            return [
                'ok' => false,
                'versions' => [],
                'status' => 404,
                'error' => 'repo not found for group ' . $group,
            ];
        }

        try {
            $result = $this->runGit([
                '--git-dir=' . $repoPath,
                'log',
                '--diff-filter=AM',
                '--max-count=' . $this->maxVersions(),
                '--format=%H%x1f%an%x1f%ae%x1f%ai%x1f%B%x1e',
                '--',
                (string) $node,
            ]);

            if (! $result['ok']) {
                return [
                    'ok' => false,
                    'versions' => [],
                    'status' => 500,
                    'error' => $result['error'],
                ];
            }

            $versions = $this->parseVersionLog((string) $result['output']);

            if ($versions === []) {
                return [
                    'ok' => false,
                    'versions' => [],
                    'status' => 404,
                    'error' => 'stored history not found',
                ];
            }

            return [
                'ok' => true,
                'versions' => $versions,
                'status' => 200,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'versions' => [],
                'status' => 500,
                'error' => get_class($e) . ': ' . $e->getMessage(),
            ];
        }
    }

    public function versionConfig(string $nodeFull, string $oid): array
    {
        [$group, $node] = $this->parseNodeFull($nodeFull);

        if (! $this->safeSegment($node)) {
            return [
                'ok' => false,
                'config' => null,
                'bytes' => null,
                'lines' => null,
                'status' => 400,
                'error' => 'invalid node_full/node',
            ];
        }

        if (! $this->safeSegment($group)) {
            return [
                'ok' => false,
                'config' => null,
                'bytes' => null,
                'lines' => null,
                'status' => 400,
                'error' => 'group is required',
            ];
        }

        if (! $this->safeOid($oid)) {
            return [
                'ok' => false,
                'config' => null,
                'bytes' => null,
                'lines' => null,
                'status' => 400,
                'error' => 'invalid oid',
            ];
        }

        $repoPath = $this->repoPathForGroup((string) $group);

        if ($repoPath === null) {
            return [
                'ok' => false,
                'config' => null,
                'bytes' => null,
                'lines' => null,
                'status' => 404,
                'error' => 'repo not found for group ' . $group,
            ];
        }

        try {
            $result = $this->runGit([
                '--git-dir=' . $repoPath,
                'show',
                $oid . ':' . $node,
            ]);

            if (! $result['ok']) {
                return [
                    'ok' => false,
                    'config' => null,
                    'bytes' => null,
                    'lines' => null,
                    'status' => 404,
                    'error' => trim((string) $result['error']) ?: 'stored version not found',
                ];
            }

            $content = (string) $result['output'];
            $bytes = strlen($content);
            $maxBytes = $this->maxConfigBytes();

            if ($bytes > $maxBytes) {
                return [
                    'ok' => false,
                    'config' => null,
                    'bytes' => $bytes,
                    'lines' => null,
                    'status' => 413,
                    'error' => 'stored version exceeds max_config_bytes',
                ];
            }

            return [
                'ok' => true,
                'config' => $content,
                'bytes' => $bytes,
                'lines' => substr_count($content, "\n"),
                'status' => 200,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'config' => null,
                'bytes' => null,
                'lines' => null,
                'status' => 500,
                'error' => get_class($e) . ': ' . $e->getMessage(),
            ];
        }
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
     * @return array{0: string|null, 1: string|null}
     */
    private function parseNodeFull(string $nodeFull): array
    {
        if ($nodeFull === '') {
            return [null, null];
        }

        $position = strrpos($nodeFull, '/');

        if ($position === false) {
            return [null, $nodeFull];
        }

        return [
            substr($nodeFull, 0, $position),
            substr($nodeFull, $position + 1),
        ];
    }

    private function safeSegment(?string $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        if (str_contains($value, "\0") || str_contains($value, "\n") || str_contains($value, "\r")) {
            return false;
        }

        if (str_contains($value, '/') || str_contains($value, '\\') || str_contains($value, '..')) {
            return false;
        }

        return ! str_starts_with($value, '-');
    }

    private function maxVersions(): int
    {
        $value = config('oxidized-history.max_versions', 200);
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($integer) ? $integer : 200;
    }

    private function maxConfigBytes(): int
    {
        $value = config('oxidized-history.max_config_bytes', 2000000);
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($integer) ? $integer : 2000000;
    }

    private function safeOid(string $value): bool
    {
        return preg_match('/\A[0-9a-fA-F]{40}\z/', $value) === 1;
    }

    private function repoPathForGroup(string $group): ?string
    {
        $storageRoot = $this->storageRoot();
        $groups = config('oxidized-history.git_groups', []);

        if (is_array($groups) && isset($groups[$group]) && is_string($groups[$group])) {
            $explicitPath = $this->repositoryPathFromEntry($storageRoot, $groups[$group]);

            if ($explicitPath !== null && $this->gitRepositoryPath($explicitPath)) {
                return $explicitPath;
            }
        }

        foreach ([$group . '.git', $group] as $entry) {
            $path = $this->repositoryPathFromEntry($storageRoot, $entry);

            if ($path !== null && $this->gitRepositoryPath($path)) {
                return $path;
            }
        }

        return null;
    }

    private function repositoryPathFromEntry(string $storageRoot, string $entry): ?string
    {
        if (! $this->safeSegment($entry)) {
            return null;
        }

        return $storageRoot . '/' . $entry;
    }

    private function gitRepositoryPath(string $path): bool
    {
        return is_dir($path)
            && is_file($path . '/HEAD')
            && is_dir($path . '/objects')
            && is_readable($path . '/HEAD')
            && is_readable($path . '/objects');
    }

    /**
     * @param array<int, string> $arguments
     * @return array{ok: bool, output: string, error: string|null}
     */
    private function runGit(array $arguments): array
    {
        $process = new Process(array_merge(['git'], $arguments));
        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            return [
                'ok' => false,
                'output' => $process->getOutput(),
                'error' => trim($process->getErrorOutput()) ?: 'git command failed',
            ];
        }

        return [
            'ok' => true,
            'output' => $process->getOutput(),
            'error' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseVersionLog(string $output): array
    {
        $versions = [];

        foreach (explode("\x1e", $output) as $record) {
            $record = trim($record);

            if ($record === '') {
                continue;
            }

            $parts = explode("\x1f", $record, 5);

            if (count($parts) !== 5) {
                continue;
            }

            [$oid, $authorName, $authorEmail, $time, $message] = $parts;

            if (! preg_match('/\A[0-9a-fA-F]{40}\z/', $oid)) {
                continue;
            }

            $message = trim($message);

            $versions[] = [
                'date' => $time,
                'time' => $time,
                'oid' => $oid,
                'author' => [
                    'name' => $authorName,
                    'email' => $authorEmail,
                    'time' => $time,
                ],
                'message' => $message,
            ];
        }

        return $versions;
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
