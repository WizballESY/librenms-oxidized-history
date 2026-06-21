<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Services;

use Symfony\Component\Process\Process;

class LocalGitRepositoryDiscovery
{
    public function findGroupForNode(string $node): ?string
    {
        if (! $this->safeSegment($node)) {
            return null;
        }

        foreach ($this->repositoryPaths() as $group => $repositoryPath) {
            if ($this->repositoryHasNodeHistory($repositoryPath, $node)) {
                return $group;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function repositoryPaths(): array
    {
        $storageRoot = $this->storageRoot();
        $repositories = [];

        $groups = config('oxidized-history.git_groups', []);
        if (is_array($groups)) {
            foreach ($groups as $group => $entry) {
                if (! is_string($group) || ! is_string($entry)) {
                    continue;
                }

                if (! $this->safeSegment($group) || ! $this->safeSegment($entry)) {
                    continue;
                }

                $path = $storageRoot . '/' . $entry;
                if ($this->gitRepositoryPath($path)) {
                    $repositories[$group] = $path;
                }
            }
        }

        foreach (glob($storageRoot . '/*', GLOB_ONLYDIR) ?: [] as $path) {
            if (! $this->gitRepositoryPath($path)) {
                continue;
            }

            $name = basename($path);
            $group = str_ends_with($name, '.git') ? substr($name, 0, -4) : $name;

            if (! $this->safeSegment($group) || isset($repositories[$group])) {
                continue;
            }

            $repositories[$group] = $path;
        }

        ksort($repositories, SORT_NATURAL | SORT_FLAG_CASE);

        return $repositories;
    }

    private function repositoryHasNodeHistory(string $repositoryPath, string $node): bool
    {
        $result = $this->runGit([
            '--git-dir=' . $repositoryPath,
            'log',
            '--diff-filter=AM',
            '--max-count=1',
            '--format=%H',
            '--',
            $node,
        ]);

        return $result['ok'] && trim($result['output']) !== '';
    }

    private function storageRoot(): string
    {
        return rtrim((string) config('oxidized-history.git_storage_root', '/opt/librenms/.config/oxidized'), '/');
    }

    private function gitRepositoryPath(string $path): bool
    {
        return is_dir($path)
            && is_file($path . '/HEAD')
            && is_dir($path . '/objects')
            && is_readable($path . '/HEAD')
            && is_readable($path . '/objects');
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
}
