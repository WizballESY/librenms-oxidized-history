<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Services;

use App\Models\Device;

class OxidizedNodeResolver
{
    /**
     * @return array{group: string|null, node: string, node_full: string|null, warning: string|null}
     */
    public function resolve(Device $device): array
    {
        $group = $this->resolveGroup($device);
        $node = $this->resolveNode($device);

        return [
            'group' => $group,
            'node' => $node,
            'node_full' => $group ? $group . '/' . $node : null,
            'warning' => $group ? null : 'No Oxidized group mapping found for this device.',
        ];
    }

    private function resolveGroup(Device $device): ?string
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

        $defaultGroup = config('oxidized-history.default_group');

        return is_string($defaultGroup) && $defaultGroup !== '' ? $defaultGroup : null;
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
