<x-panel title="Historical Config">
    <div class="row">
        <div class="col-md-12">
            <p class="text-muted">
                Stored Oxidized configuration history from the configured history API.
            </p>

            <table class="table table-condensed">
                <tbody>
                    <tr>
                        <th style="width: 180px;">Device</th>
                        <td>{{ $device->hostname }}</td>
                    </tr>
                    <tr>
                        <th>Resolved Oxidized node</th>
                        <td>
                            @if(($data['resolved']['node_full'] ?? null))
                                <code>{{ $data['resolved']['node_full'] }}</code>
                            @else
                                <span class="text-danger">Not resolved</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>History API</th>
                        <td><code>{{ $data['api_url'] ?? '' }}</code></td>
                    </tr>
                </tbody>
            </table>

            @if(!($data['history']['ok'] ?? false))
                <div class="alert alert-warning">
                    <strong>No historical config available.</strong>
                    <br>
                    {{ $data['history']['error'] ?? 'Unknown error' }}
                    @if(isset($data['history']['status']) && $data['history']['status'])
                        <br>
                        HTTP status: {{ $data['history']['status'] }}
                    @endif
                </div>
            @else
                <div class="alert alert-success">
                    <strong>Historical config found.</strong>
                    {{ count($data['history']['versions'] ?? []) }} stored versions returned by the history API.
                </div>

                <table class="table table-condensed table-striped">
                    <thead>
                        <tr>
                            <th style="width: 220px;">Time</th>
                            <th style="width: 330px;">Commit</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($data['history']['versions'] ?? []) as $version)
                            <tr>
                                <td>{{ $version['time'] ?? $version['date'] ?? '' }}</td>
                                <td><code>{{ $version['oid'] ?? '' }}</code></td>
                                <td>{{ $version['message'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-panel>
