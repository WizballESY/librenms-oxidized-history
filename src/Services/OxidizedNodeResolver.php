<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Services;

use App\Facades\LibrenmsConfig;
use App\Models\Device;

class OxidizedNodeResolver
{
    public function __construct(private readonly LocalGitRepositoryDiscovery $localGitRepositoryDiscovery)
    {
    }

    /**
     * @return array{group: string|null, node: string, node_full: string|null, warning: string|null}
     */
    public function resolve(Device $device): array
    {
        $node = $this->resolveNode($device);
        $group = $this->resolveGroup($device, $node);

        return [
            'group' => $group,
            'node' => $node,
            'node_full' => $group ? $group . '/' . $node : null,
            'warning' => $group ? null : 'No Oxidized group mapping found for this device.',
        ];
    }

    private function resolveGroup(Device $device, string $node): ?string
    {
        return $this->resolveGroupFromLibreNmsOxidizedMaps($device)
            ?? $this->localGitRepositoryDiscovery->findGroupForNode($node)
            ?? $this->resolveGroupFromPluginMap($device)
            ?? $this->resolveDefaultGroup();
    }

    private function resolveGroupFromLibreNmsOxidizedMaps(Device $device): ?string
    {
        $maps = LibrenmsConfig::get('oxidized.maps.group', []);

        if (! is_array($maps)) {
            return null;
        }

        foreach ($maps as $field => $rules) {
            if (! is_array($rules)) {
                continue;
            }

            $deviceValue = $this->deviceFieldValue($device, (string) $field);

            if ($deviceValue === null || $deviceValue === '') {
                continue;
            }

            foreach ($rules as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $match = (string) ($rule['match'] ?? '');
                $value = (string) ($rule['value'] ?? '');

                if ($match === '' || $value === '') {
                    continue;
                }

                if (strcasecmp($deviceValue, $match) === 0) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function resolveGroupFromPluginMap(Device $device): ?string
    {
        $map = config('oxidized-history.group_os_map', []);
        $os = strtolower((string) $device->os);

        if (isset($map[$os])) {
            return $map[$os];
        }

        $type = strtolower((string) $device->type);

        if ($type !== '' && isset($map[$type])) {
            return $map[$type];
        }

        return null;
    }

    private function resolveDefaultGroup(): ?string
    {
        $libreNmsDefaultGroup = LibrenmsConfig::get('oxidized.default_group');

        if (is_string($libreNmsDefaultGroup) && $libreNmsDefaultGroup !== '') {
            return $libreNmsDefaultGroup;
        }

        $pluginDefaultGroup = config('oxidized-history.default_group');

        return is_string($pluginDefaultGroup) && $pluginDefaultGroup !== '' ? $pluginDefaultGroup : null;
    }

    private function deviceFieldValue(Device $device, string $field): ?string
    {
        $field = strtolower($field);

        $value = match ($field) {
            'os' => $device->os,
            'type' => $device->type,
            'hostname' => $device->hostname,
            'ip' => $device->ip,
            'hardware' => $device->hardware,
            'sysname' => $device->getAttribute('sysName'),
            default => $device->getAttribute($field),
        };

        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : null;
    }

    private function resolveNode(Device $device): string
    {
        $hostname = (string) $device->hostname;

        if ($hostname !== '') {
            return $hostname;
        }

        return (string) $device->ip;
    }
}
