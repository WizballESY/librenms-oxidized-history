<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Services;

use WizballEsy\LibreNmsOxidizedHistory\Contracts\HistoryProvider;

class ApiHistoryProvider implements HistoryProvider
{
    public function __construct(private readonly HistoryApiClient $client)
    {
    }

    public function health(): array
    {
        return $this->client->health();
    }

    public function versions(string $nodeFull): array
    {
        return $this->client->versions($nodeFull);
    }

    public function versionConfig(string $nodeFull, string $oid): array
    {
        return $this->client->versionConfig($nodeFull, $oid);
    }

    public function diff(string $nodeFull, string $oidNew, string $oidOld, bool $includePatch = true): array
    {
        return $this->client->diff($nodeFull, $oidNew, $oidOld, $includePatch);
    }
}
