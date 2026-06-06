@extends('layouts.librenmsv1')

@section('content')
<x-device.page :device="$device">
    @php
        $resolved = $data['resolved'] ?? [];
        $history = $data['history'] ?? [];
        $versions = $history['versions'] ?? [];
        $selectedOid = $data['selected_oid'] ?? null;
        $selectedPreviousOid = $data['selected_previous_oid'] ?? null;
        $selectedConfig = $data['selected_config'] ?? [];
        $selectedDiff = $data['selected_diff'] ?? [];
        $showDiff = (bool) ($data['show_diff'] ?? false);
        $nodeFull = $resolved['node_full'] ?? null;
    @endphp

    <x-panel title="Historical Config">
        <div class="row">
            <div class="col-md-12">
                <div class="clearfix" style="margin-bottom: 15px;">
                    <div class="pull-left">
                        <div class="text-muted">
                            Stored Oxidized configuration history from the configured history API.
                        </div>
                        <div>
                            <strong>Oxidized node:</strong>
                            @if($nodeFull)
                                <code>{{ $nodeFull }}</code>
                            @else
                                <span class="text-danger">Not resolved</span>
                            @endif

                            <span class="text-muted" style="margin-left: 15px;">
                                API:
                                <code>{{ $data['api_url'] ?? '' }}</code>
                            </span>
                        </div>
                    </div>

                    <div class="pull-right text-right">
                        @if($history['ok'] ?? false)
                            <span class="label label-success">History found</span>
                            <div class="text-muted">
                                {{ count($versions) }} stored versions
                            </div>
                        @else
                            <span class="label label-warning">No history</span>
                        @endif
                    </div>
                </div>

                @if(!($history['ok'] ?? false))
                    <div class="alert alert-warning">
                        <strong>No historical config available.</strong>
                        <br>
                        {{ $history['error'] ?? 'Unknown error' }}
                        @if(isset($history['status']) && $history['status'])
                            <br>
                            HTTP status: {{ $history['status'] }}
                        @endif
                    </div>
                @else
                    <form method="get" class="form-inline" style="margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="historical-config-oid">Version</label>
                            <select id="historical-config-oid" name="oid" class="form-control input-sm">
                                @foreach($versions as $version)
                                    @php
                                        $oid = $version['oid'] ?? '';
                                        $label = trim(($version['time'] ?? $version['date'] ?? '') . ' - ' . substr($oid, 0, 12) . ' - ' . ($version['message'] ?? ''));
                                    @endphp
                                    <option value="{{ $oid }}" @selected($selectedOid === $oid)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">
                            View config
                        </button>

                        @if($selectedPreviousOid)
                            @if($showDiff)
                                <a class="btn btn-default btn-sm"
                                   href="{{ request()->url() . '?' . http_build_query(['oid' => $selectedOid]) }}">
                                    Hide diff
                                </a>
                            @else
                                <a class="btn btn-info btn-sm"
                                   href="{{ request()->url() . '?' . http_build_query(['oid' => $selectedOid, 'show_diff' => 1]) }}">
                                    Diff with previous
                                </a>
                            @endif
                        @else
                            <button class="btn btn-default btn-sm" type="button" disabled>
                                No previous version
                            </button>
                        @endif
                    </form>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>Selected version</strong>
                                </div>
                                <table class="table table-condensed" style="margin-bottom: 0;">
                                    <tbody>
                                        <tr>
                                            <th>Commit</th>
                                            <td>
                                                @if($selectedOid)
                                                    <code>{{ substr($selectedOid, 0, 12) }}</code>
                                                @else
                                                    <span class="text-muted">None</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Previous</th>
                                            <td>
                                                @if($selectedPreviousOid)
                                                    <code>{{ substr($selectedPreviousOid, 0, 12) }}</code>
                                                @else
                                                    <span class="text-muted">None</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Lines</th>
                                            <td>{{ $selectedConfig['lines'] ?? 0 }}</td>
                                        </tr>
                                        <tr>
                                            <th>Bytes</th>
                                            <td>{{ $selectedConfig['bytes'] ?? 0 }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>History</strong>
                                </div>
                                <div class="list-group" style="margin-bottom: 0;">
                                    @foreach($versions as $version)
                                        @php
                                            $oid = $version['oid'] ?? '';
                                            $isSelected = $selectedOid === $oid;
                                        @endphp
                                        <a class="list-group-item {{ $isSelected ? 'active' : '' }}"
                                           href="{{ request()->url() . '?' . http_build_query(['oid' => $oid]) }}">
                                            <div>
                                                <strong>{{ $version['time'] ?? $version['date'] ?? '' }}</strong>
                                            </div>
                                            <div>
                                                <code>{{ substr($oid, 0, 12) }}</code>
                                            </div>
                                            <small>{{ $version['message'] ?? '' }}</small>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>Compliance</strong>
                                </div>
                                <div class="panel-body">
                                    <span class="text-muted">
                                        Reserved for future Config Auditor integration.
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-9">
                            @if($showDiff && $selectedPreviousOid)
                                @if($selectedDiff['ok'] ?? false)
                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <strong>Diff with previous</strong>
                                            <span class="text-muted">
                                                {{ substr($selectedOid ?? '', 0, 12) }}
                                                vs
                                                {{ substr($selectedPreviousOid ?? '', 0, 12) }}
                                            </span>
                                        </div>

                                        @foreach(($selectedDiff['files'] ?? []) as $file)
                                            <div class="panel-body" style="padding-bottom: 0;">
                                                <strong>{{ $file['old_file'] ?? '' }}</strong>
                                                →
                                                <strong>{{ $file['new_file'] ?? '' }}</strong>
                                                <span class="text-muted">
                                                    +{{ $file['additions'] ?? 0 }}
                                                    -{{ $file['deletions'] ?? 0 }}
                                                </span>
                                            </div>
                                            <pre style="max-height: 500px; overflow: auto; white-space: pre; margin: 0;">{{ $file['patch'] ?? '' }}</pre>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <strong>Could not load diff.</strong>
                                        <br>
                                        {{ $selectedDiff['error'] ?? 'Unknown error' }}
                                    </div>
                                @endif
                            @endif

                            @if($selectedConfig['ok'] ?? false)
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <strong>Config</strong>
                                        @if($selectedOid)
                                            <span class="text-muted">
                                                {{ substr($selectedOid, 0, 12) }}
                                            </span>
                                        @endif
                                        <span class="pull-right text-muted">
                                            {{ $selectedConfig['lines'] ?? 0 }} lines,
                                            {{ $selectedConfig['bytes'] ?? 0 }} bytes
                                        </span>
                                    </div>
                                    <pre style="max-height: 850px; overflow: auto; white-space: pre; margin: 0;">{{ $selectedConfig['config'] ?? '' }}</pre>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <strong>Could not load selected config.</strong>
                                    <br>
                                    {{ $selectedConfig['error'] ?? 'Unknown error' }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-panel>
</x-device.page>
@endsection
