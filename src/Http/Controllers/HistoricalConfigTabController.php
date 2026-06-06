<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use LibreNMS\Interfaces\UI\DeviceTab;
use WizballEsy\LibreNmsOxidizedHistory\Services\HistoryApiClient;
use WizballEsy\LibreNmsOxidizedHistory\Services\OxidizedNodeResolver;

class HistoricalConfigTabController implements DeviceTab
{
    public function visible(Device $device): bool
    {
        return true;
    }

    public function slug(): string
    {
        return 'historical-config';
    }

    public function icon(): string
    {
        return 'fa-history';
    }

    public function name(): string
    {
        return __('Historical Config');
    }

    public function data(Device $device, Request $request): array
    {
        $resolver = app(OxidizedNodeResolver::class);
        $client = app(HistoryApiClient::class);

        $resolved = $resolver->resolve($device);
        $history = [
            'ok' => false,
            'versions' => [],
            'status' => null,
            'error' => $resolved['warning'],
        ];

        $selectedOid = null;
        $selectedConfig = [
            'ok' => false,
            'config' => null,
            'bytes' => null,
            'lines' => null,
            'status' => null,
            'error' => null,
        ];

        if ($resolved['node_full']) {
            $history = $client->versions($resolved['node_full']);

            if ($history['ok'] && ! empty($history['versions'])) {
                $validOids = array_values(array_filter(array_map(
                    static fn ($version) => is_array($version) ? ($version['oid'] ?? null) : null,
                    $history['versions']
                )));

                $requestedOid = (string) $request->query('oid', '');
                $selectedOid = in_array($requestedOid, $validOids, true)
                    ? $requestedOid
                    : (string) $validOids[0];

                $selectedConfig = $client->versionConfig($resolved['node_full'], $selectedOid);
            }
        }

        return [
            'resolved' => $resolved,
            'history' => $history,
            'selected_oid' => $selectedOid,
            'selected_config' => $selectedConfig,
            'api_url' => rtrim((string) config('oxidized-history.api_url'), '/'),
        ];
    }
}
