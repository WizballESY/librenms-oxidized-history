<x-panel title="Historical Config">
    <div class="alert alert-info">
        <strong>Historical Config tab loaded.</strong>
        <br>
        This is a proof-of-concept tab from the LibreNMS Oxidized History package.
    </div>

    <table class="table table-condensed table-striped">
        <tbody>
            <tr>
                <th>Device ID</th>
                <td>{{ $data['device_id'] ?? $device->device_id }}</td>
            </tr>
            <tr>
                <th>Hostname</th>
                <td>{{ $data['hostname'] ?? $device->hostname }}</td>
            </tr>
            <tr>
                <th>Message</th>
                <td>{{ $data['message'] ?? 'No message' }}</td>
            </tr>
        </tbody>
    </table>
</x-panel>
