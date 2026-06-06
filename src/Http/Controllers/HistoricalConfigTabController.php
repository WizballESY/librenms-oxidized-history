<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use LibreNMS\Interfaces\UI\DeviceTab;

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
        return [
            'message' => 'Historical Config tab proof-of-concept is loaded from wizballesy/librenms-oxidized-history.',
            'device_id' => $device->device_id,
            'hostname' => $device->hostname,
        ];
    }
}
