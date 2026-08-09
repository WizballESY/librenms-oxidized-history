<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Http\Controllers;

use App\ApiClients\Oxidized;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use LibreNMS\Interfaces\UI\DeviceTab;
use WizballEsy\LibreNmsOxidizedHistory\Contracts\HistoryProvider;
use WizballEsy\LibreNmsOxidizedHistory\Services\OxidizedNodeResolver;

class HistoricalConfigTabController implements DeviceTab
{
    public function visible(Device $device): bool
    {
        return Gate::allows('show-config', $device);
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
        Gate::authorize('show-config', $device);

        $resolver = app(OxidizedNodeResolver::class);
        $provider = app(HistoryProvider::class);

        $backendHealth = $provider->health();
        $resolved = $resolver->resolve($device);

        $history = [
            'ok' => false,
            'versions' => [],
            'status' => null,
            'error' => $resolved['warning'],
        ];

        if ($resolved['node_full']) {
            $history = $provider->versions($resolved['node_full']);
        }

        return [
            'resolved' => $resolved,
            'history' => $history,
            'backend_health' => $backendHealth,
            'config_ui' => [
                'urls' => [
                    'backups' => route(
                        'oxidized-history.backups',
                        $device->device_id
                    ),
                    'backup' => route(
                        'oxidized-history.backup',
                        $device->device_id
                    ),
                    'diff' => route(
                        'oxidized-history.diff',
                        $device->device_id
                    ),
                    'take_backup' => route(
                        'oxidized-history.take-backup',
                        $device->device_id
                    ),
                    'backup_status' => route(
                        'oxidized-history.backup-status',
                        $device->device_id
                    ),
                ],
                'hostname' => $device->hostname,
                'os' => $device->os,
                'can_take_backup' => (
                    Gate::allows('oxidized.refresh')
                    && Oxidized::isConfigured()
                ),
                'config_highlighting' => LibrenmsConfig::getOsSetting(
                    $device->os,
                    'config_highlighting'
                ),
                'messages' => [
                    'device_not_found' => 'The Oxidized node could not be resolved.',
                    'history_unavailable' => 'Historical configuration is unavailable.',
                    'no_backups' => 'No historical backups are available.',
                    'backup_not_found' => 'The selected backup could not be loaded.',
                    'diff_unavailable' => 'The selected diff could not be loaded.',
                    'oxidized_unavailable' => 'Oxidized is not available.',
                    'backup_queue_failed' => 'Could not queue a backup in Oxidized.',
                    'backup_failed' => 'Oxidized could not complete the backup.',
                    'backup_status_timeout' => 'Timed out waiting for Oxidized to finish the backup.',
                    'request_failed' => 'The request failed.',
                ],
            ],
        ];
    }

    public function backups(Device $device): JsonResponse
    {
        Gate::authorize('show-config', $device);

        $resolver = app(OxidizedNodeResolver::class);
        $provider = app(HistoryProvider::class);

        $resolved = $resolver->resolve($device);

        if (! $resolved['node_full']) {
            return $this->errorResponse('device_not_found', 404);
        }

        $history = $provider->versions($resolved['node_full']);

        if (! ($history['ok'] ?? false)) {
            return $this->errorResponse(
                'history_unavailable',
                $this->responseStatus($history, 503)
            );
        }

        $backups = [];

        foreach ($history['versions'] ?? [] as $version) {
            if (! is_array($version)) {
                continue;
            }

            $oid = (string) ($version['oid'] ?? '');

            if (preg_match('/\A[0-9a-fA-F]{40}\z/', $oid) !== 1) {
                continue;
            }

            $backups[] = [
                'id' => $oid,
                'date' => $this->versionTimestamp($version),
                'type' => 'TEXT',
            ];
        }

        if ($backups === []) {
            return $this->errorResponse('no_backups', 404);
        }

        return response()->json([
            'backups' => $backups,
            'page' => 0,
            'totalPages' => 1,
            'total' => count($backups),
        ]);
    }

    public function backup(Device $device, Request $request): JsonResponse
    {
        Gate::authorize('show-config', $device);

        $validated = $request->validate([
            'backup' => [
                'nullable',
                'string',
                'size:40',
                'regex:/\A[0-9a-fA-F]{40}\z/',
            ],
        ]);

        $resolver = app(OxidizedNodeResolver::class);
        $provider = app(HistoryProvider::class);

        $resolved = $resolver->resolve($device);

        if (! $resolved['node_full']) {
            return $this->errorResponse('device_not_found', 404);
        }

        $requestedOid = $validated['backup'] ?? null;

        if ($requestedOid === null) {
            $history = $provider->versions($resolved['node_full']);

            if (! ($history['ok'] ?? false)) {
                return $this->errorResponse(
                    'history_unavailable',
                    $this->responseStatus($history, 503)
                );
            }

            $version = $history['versions'][0] ?? null;

            if (! is_array($version)) {
                return $this->errorResponse('no_backups', 404);
            }

            $requestedOid = (string) ($version['oid'] ?? '');

            if (preg_match('/\A[0-9a-fA-F]{40}\z/', $requestedOid) !== 1) {
                return $this->errorResponse('backup_not_found', 404);
            }

            $config = $provider->versionConfig(
                $resolved['node_full'],
                $requestedOid
            );

            if (! ($config['ok'] ?? false)) {
                return $this->errorResponse(
                    'backup_not_found',
                    $this->responseStatus($config, 404)
                );
            }

            return response()->json([
                'id' => $requestedOid,
                'date' => $this->versionTimestamp($version),
                'type' => 'TEXT',
                'content' => (string) ($config['config'] ?? ''),
            ]);
        }

        $config = $provider->versionConfig(
            $resolved['node_full'],
            $requestedOid
        );

        if (! ($config['ok'] ?? false)) {
            return $this->errorResponse(
                'backup_not_found',
                $this->responseStatus($config, 404)
            );
        }

        return response()->json([
            'id' => $requestedOid,
            'content' => (string) ($config['config'] ?? ''),
        ]);
    }

    public function backupStatus(Device $device): JsonResponse
    {
        Gate::authorize('show-config', $device);
        Gate::authorize('oxidized.refresh');

        if (! Oxidized::isConfigured()) {
            return $this->errorResponse('oxidized_unavailable', 503);
        }

        $oxidized = new Oxidized();
        $node = $oxidized->findNode($device);

        if ($node === null) {
            return $this->errorResponse(
                $oxidized->lastError() === 'unreachable'
                    ? 'oxidized_unavailable'
                    : 'device_not_found',
                $oxidized->lastError() === 'unreachable' ? 503 : 404
            );
        }

        $last = is_array($node['last'] ?? null)
            ? $node['last']
            : [];

        return response()->json([
            'last_start' => $last['start'] ?? null,
            'last_end' => $last['end'] ?? null,
            'last_status' => isset($last['status'])
                ? (string) $last['status']
                : null,
            'last_time' => $last['time'] ?? null,
        ]);
    }

    public function takeBackup(Device $device): JsonResponse
    {
        Gate::authorize('show-config', $device);
        Gate::authorize('oxidized.refresh');

        if (! Oxidized::isConfigured()) {
            return $this->errorResponse('oxidized_unavailable', 503);
        }

        $oxidized = new Oxidized();
        $node = $oxidized->findNode($device);

        if ($node === null) {
            return $this->errorResponse(
                $oxidized->lastError() === 'unreachable'
                    ? 'oxidized_unavailable'
                    : 'device_not_found',
                $oxidized->lastError() === 'unreachable' ? 503 : 404
            );
        }

        $nodeName = trim((string) ($node['name'] ?? ''));

        if ($nodeName === '') {
            return $this->errorResponse('device_not_found', 404);
        }

        $last = is_array($node['last'] ?? null)
            ? $node['last']
            : [];

        $baseline = [
            'last_start' => $last['start'] ?? null,
            'last_end' => $last['end'] ?? null,
            'last_status' => isset($last['status'])
                ? (string) $last['status']
                : null,
        ];

        $username = (string) (Auth::user()?->username ?? 'not_provided');

        $queued = $oxidized->updateNode(
            $nodeName,
            'LibreNMS GUI backup',
            $username
        );

        if (! $queued) {
            return $this->errorResponse('backup_queue_failed', 502);
        }

        return response()->json([
            'status' => 'queued',
            'baseline' => $baseline,
        ]);
    }

    public function diff(Device $device, Request $request): JsonResponse
    {
        Gate::authorize('show-config', $device);

        $validated = $request->validate([
            'orig' => [
                'required',
                'string',
                'size:40',
                'regex:/\A[0-9a-fA-F]{40}\z/',
            ],
            'rev' => [
                'required',
                'string',
                'size:40',
                'different:orig',
                'regex:/\A[0-9a-fA-F]{40}\z/',
            ],
        ]);

        $resolver = app(OxidizedNodeResolver::class);
        $provider = app(HistoryProvider::class);

        $resolved = $resolver->resolve($device);

        if (! $resolved['node_full']) {
            return $this->errorResponse('device_not_found', 404);
        }

        /*
         * HistoryProvider::diff() expects:
         *   newer OID first, older OID second.
         *
         * The LibreNMS-style frontend sends:
         *   orig = older
         *   rev  = newer
         */
        $diff = $provider->diff(
            $resolved['node_full'],
            $validated['rev'],
            $validated['orig'],
            false
        );

        if (! ($diff['ok'] ?? false)) {
            return $this->errorResponse(
                'diff_unavailable',
                $this->responseStatus($diff, 404)
            );
        }

        return response()->json([
            'groups' => $diff['groups'] ?? [],
        ]);
    }

    /**
     * @param array<string, mixed> $version
     */
    private function versionTimestamp(array $version): ?int
    {
        $value = $version['time'] ?? $version['date'] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : $timestamp;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function responseStatus(array $result, int $fallback): int
    {
        $status = (int) ($result['status'] ?? 0);

        return $status >= 400 && $status <= 599
            ? $status
            : $fallback;
    }

    private function errorResponse(string $error, int $status): JsonResponse
    {
        return response()->json([
            'error' => $error,
        ], $status);
    }
}
