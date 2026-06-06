@extends('layouts.librenmsv1')

@section('content')
<x-device.page :device="$device">
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

                    <form method="get" class="form-inline" style="margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="historical-config-oid">Version</label>
                            <select id="historical-config-oid" name="oid" class="form-control input-sm">
                                @foreach(($data['history']['versions'] ?? []) as $version)
                                    @php
                                        $oid = $version['oid'] ?? '';
                                        $label = trim(($version['time'] ?? $version['date'] ?? '') . ' - ' . substr($oid, 0, 12) . ' - ' . ($version['message'] ?? ''));
                                    @endphp
                                    <option value="{{ $oid }}" @selected(($data['selected_oid'] ?? null) === $oid)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">View version</button>
                    </form>

                    @if(($data['selected_config']['ok'] ?? false))
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <strong>Selected config</strong>
                                @if($data['selected_oid'] ?? null)
                                    <span class="text-muted">
                                        {{ substr($data['selected_oid'], 0, 12) }}
                                    </span>
                                @endif
                                <span class="pull-right text-muted">
                                    {{ $data['selected_config']['lines'] ?? 0 }} lines,
                                    {{ $data['selected_config']['bytes'] ?? 0 }} bytes
                                </span>
                            </div>
                            <pre style="max-height: 700px; overflow: auto; white-space: pre; margin: 0;">{{ $data['selected_config']['config'] ?? '' }}</pre>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <strong>Could not load selected config.</strong>
                            <br>
                            {{ $data['selected_config']['error'] ?? 'Unknown error' }}
                        </div>
                    @endif

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
</x-device.page>
@endsection
