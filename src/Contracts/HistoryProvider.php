<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Contracts;

interface HistoryProvider
{
    /**
     * @return array{ok: bool, payload: array<string, mixed>|null, status: int|null, error: string|null}
     */
    public function health(): array;

    /**
     * @return array{ok: bool, versions: array<int, array<string, mixed>>, status: int|null, error: string|null}
     */
    public function versions(string $nodeFull): array;

    /**
     * @return array{ok: bool, config: string|null, bytes: int|null, lines: int|null, status: int|null, error: string|null}
     */
    public function versionConfig(string $nodeFull, string $oid): array;

    /**
     * @return array{ok: bool, files: array<int, array<string, mixed>>, status: int|null, error: string|null}
     */
    public function diff(string $nodeFull, string $oidNew, string $oidOld, bool $includePatch = true): array;
}
