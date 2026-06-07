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
        $resolvedGroup = $resolved['group'] ?? null;

        $apiHealth = $data['api_health'] ?? [];
        $apiPayload = is_array($apiHealth['payload'] ?? null) ? $apiHealth['payload'] : [];
        $apiConfig = is_array($apiPayload['config'] ?? null) ? $apiPayload['config'] : [];
        $apiLimits = is_array($apiConfig['limits'] ?? null) ? $apiConfig['limits'] : [];
        $apiRepos = is_array($apiConfig['discovered_repositories'] ?? null) ? $apiConfig['discovered_repositories'] : [];
        $apiOk = (bool) ($apiHealth['ok'] ?? false);
        $apiDebug = request()->boolean('api_debug');

        $selectedVersion = null;
        foreach ($versions as $version) {
            if (($version['oid'] ?? null) === $selectedOid) {
                $selectedVersion = $version;
                break;
            }
        }

        $latestVersion = $versions[0] ?? null;
        $latestTime = is_array($latestVersion) ? ($latestVersion['time'] ?? $latestVersion['date'] ?? '') : '';

        $configText = (string) ($selectedConfig['config'] ?? '');
        $diffFiles = $selectedDiff['files'] ?? [];
        $diffText = '';
        foreach ($diffFiles as $file) {
            $diffText .= (string) ($file['patch'] ?? '');
        }

        $displayText = $showDiff ? $diffText : $configText;
        $displayLanguage = $showDiff ? 'diff' : 'ios';

        if ($displayText !== '' && class_exists('GeSHi')) {
            $geshi = new GeSHi(htmlspecialchars_decode($displayText, ENT_QUOTES | ENT_HTML5), $displayLanguage);
            $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
            $geshi->set_overall_style('color: black;');
            $renderedConfig = $geshi->parse_code();
        } else {
            $renderedConfig = '<pre style="white-space: pre; overflow: auto;">' . e($displayText) . '</pre>';
        }
    @endphp

    @if(!($history['ok'] ?? false))
        <br>
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
        <br>
        <div class="row">
            <div class="col-sm-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        History status: <strong>success</strong>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <strong>Node:</strong>
                            @if($nodeFull)
                                {{ $nodeFull }}
                            @else
                                <span class="text-danger">Not resolved</span>
                            @endif
                        </li>
                        <li class="list-group-item">
                            <strong>Oxidized group:</strong>
                            @if($resolvedGroup)
                                {{ $resolvedGroup }}
                            @else
                                <span class="text-muted">not resolved</span>
                            @endif
                        </li>
                        <li class="list-group-item">
                            <strong>IP:</strong> {{ $device->ip }}
                        </li>
                        <li class="list-group-item">
                            <strong>LibreNMS OS:</strong> {{ $device->os }}
                        </li>
                        <li class="list-group-item" style="overflow:hidden">
                            <strong>Last Stored:</strong> {{ $latestTime }}
                            <span class="label label-primary pull-right">
                                {{ count($versions) }} versions
                            </span>
                        </li>
                        <li class="list-group-item" style="overflow:hidden">
                            <strong>API:</strong>
                            @if($apiOk)
                                <span class="text-success">ok</span>
                            @else
                                <span class="text-danger">error</span>
                            @endif
                            @if(!empty($apiPayload['version']))
                                <span class="text-muted">{{ $apiPayload['version'] }}</span>
                            @endif
                            @if($apiDebug)
                                <a class="pull-right" href="{{ request()->fullUrlWithQuery(['api_debug' => 0]) }}">
                                    hide diagnostics
                                </a>
                            @else
                                <a class="pull-right" href="{{ request()->fullUrlWithQuery(['api_debug' => 1]) }}">
                                    debug
                                </a>
                            @endif
                        </li>
                    </ul>
                </div>

                @if($apiDebug || !$apiOk)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            API diagnostics:
                            @if($apiOk)
                                <strong>ok</strong>
                                <span class="label label-success pull-right">reachable</span>
                            @else
                                <strong>error</strong>
                                <span class="label label-danger pull-right">unreachable</span>
                            @endif
                        </div>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong>Service:</strong>
                                {{ $apiPayload['service'] ?? 'Unknown' }}
                            </li>
                            <li class="list-group-item">
                                <strong>Version:</strong>
                                {{ $apiPayload['version'] ?? 'Unknown' }}
                            </li>
                            <li class="list-group-item">
                                <strong>Auth:</strong>
                                {{ ($apiConfig['auth_enabled'] ?? false) ? 'enabled' : 'disabled' }}
                            </li>
                            <li class="list-group-item">
                                <strong>Limits:</strong>
                                @if(count($apiLimits) > 0)
                                    {{ $apiLimits['max_versions'] ?? '?' }} versions /
                                    {{ $apiLimits['max_config_bytes'] ?? '?' }} bytes
                                @else
                                    <span class="text-muted">not reported</span>
                                @endif
                            </li>
                            <li class="list-group-item">
                                <strong>Detected backup repos:</strong>
                                @if(count($apiRepos) > 0)
                                    {{ implode(', ', $apiRepos) }}
                                @else
                                    <span class="text-muted">not reported by this API version</span>
                                @endif
                            </li>
                            @if(!$apiOk)
                                <li class="list-group-item text-danger">
                                    <strong>Error:</strong>
                                    {{ $apiHealth['error'] ?? 'Unknown error' }}
                                    @if(!empty($apiHealth['status']))
                                        <span class="text-muted">HTTP {{ $apiHealth['status'] }}</span>
                                    @endif
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif

            </div>

            <div class="col-sm-8">
                <form class="form-horizontal" action="" method="get">
                    <div class="form-group">
                        <label for="historical-config-oid" class="col-sm-2 control-label">
                            Config version
                        </label>
                        <div class="col-sm-6">
                            <select id="historical-config-oid" name="oid" class="form-control">
                                @php $versionNumber = count($versions); @endphp
                                @foreach($versions as $version)
                                    @php
                                        $oid = $version['oid'] ?? '';
                                        $date = $version['time'] ?? $version['date'] ?? '';
                                        $prefix = '&nbsp;&nbsp;';

                                        if ($selectedOid === $oid) {
                                            $prefix = $showDiff ? '+' : '*';
                                        } elseif ($selectedPreviousOid === $oid && $showDiff) {
                                            $prefix = '&nbsp;-';
                                        }
                                    @endphp
                                    <option value="{{ $oid }}" @selected($selectedOid === $oid)>
                                        {!! $prefix !!}{{ $versionNumber }} :: {{ $date }}
                                    </option>
                                    @php $versionNumber--; @endphp
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-6">
                            <button type="submit" class="btn btn-primary btn-sm" name="show" value="1">
                                Show version
                            </button>

                            @if($selectedPreviousOid)
                                <button type="submit" class="btn btn-primary btn-sm" name="show_diff" value="1">
                                    Show diff
                                </button>
                            @else
                                <button type="button" class="btn btn-default btn-sm" disabled>
                                    No previous version
                                </button>
                            @endif

                            @if($showDiff)
                                <a class="btn btn-default btn-sm"
                                   href="{{ request()->url() . '?' . http_build_query(['oid' => $selectedOid]) }}">
                                    Back to config
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(!empty($selectedVersion['author']['name']) || !empty($selectedVersion['message']))
            <div class="panel panel-primary">
                @if(!empty($selectedVersion['author']['name']))
                    <div class="panel-heading">
                        Author: <strong>{{ $selectedVersion['author']['name'] }}</strong>
                    </div>
                @endif
                @if(!empty($selectedVersion['message']))
                    <ul class="list-group">
                        <li class="list-group-item">
                            <strong>Message:</strong> {{ $selectedVersion['message'] }}
                        </li>
                    </ul>
                @endif
            </div>
        @endif

        @if($showDiff)
            @if(!($selectedDiff['ok'] ?? false))
                <div class="alert alert-warning">
                    <strong>Could not load diff.</strong>
                    <br>
                    {{ $selectedDiff['error'] ?? 'Unknown error' }}
                </div>
            @endif
        @else
            @if(!($selectedConfig['ok'] ?? false))
                <div class="alert alert-warning">
                    <strong>Could not load selected config.</strong>
                    <br>
                    {{ $selectedConfig['error'] ?? 'Unknown error' }}
                </div>
            @endif
        @endif

        @if($displayText !== '')
            <div class="config">
                <input id="linenumbers" class="btn btn-primary" type="submit" value="Hide line numbers"/>
                {!! $renderedConfig !!}
            </div>
        @endif
    @endif
</x-device.page>
@endsection
