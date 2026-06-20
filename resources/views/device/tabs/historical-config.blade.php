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
        $apiUrl = (string) ($data['api_url'] ?? config('oxidized-history.api_url', 'http://127.0.0.1:8899'));
        $apiError = (string) ($apiHealth['error'] ?? '');
        $historyError = (string) ($history['error'] ?? 'Unknown error');

        $backendDriver = strtolower((string) ($apiPayload['driver'] ?? config('oxidized-history.driver', 'api')));
        $backendLabel = $backendDriver === 'local' ? 'Local Git' : 'History API';
        $backendOkLabel = $backendDriver === 'local' ? 'healthy' : 'reachable';
        $backendErrorLabel = $backendDriver === 'local' ? 'unhealthy' : 'unreachable';
        $backendStorageRoot = (string) ($apiConfig['storage_root'] ?? config('oxidized-history.git_storage_root', '/opt/librenms/.config/oxidized'));

        $pluginPackage = 'wizballesy/librenms-oxidized-history';
        $pluginVersion = 'unknown';
        if (class_exists(\Composer\InstalledVersions::class)) {
            $pluginVersion = \Composer\InstalledVersions::getPrettyVersion($pluginPackage) ?: 'dev';
        }
        $backendDebug = request()->boolean('backend_debug');

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

    <style>
        .historical-config-output {
            overflow: auto;
        }

        .historical-config-output pre {
            white-space: pre;
            overflow: auto;
        }

        .dark .historical-config-output,
        .dark .historical-config-output > div,
        .dark .historical-config-output pre,
        .dark .historical-config-output table,
        .dark .historical-config-output tbody,
        .dark .historical-config-output tr,
        .dark .historical-config-output td,
        .dark .historical-config-output ol,
        .dark .historical-config-output li {
            background-color: #111827 !important;
            color: #d1d5db !important;
            border-color: #374151 !important;
        }

        .dark .historical-config-output {
            border: 1px solid #374151;
            border-radius: 4px;
        }

        .historical-config-api-unavailable pre {
            white-space: pre-wrap;
            word-break: break-word;
            margin-top: 8px;
            margin-bottom: 0;
        }

        .historical-config-api-unavailable details {
            margin-top: 10px;
        }

        .dark .historical-config-api-unavailable,
        .dark .historical-config-api-unavailable .panel-body {
            background-color: #111827;
            color: #d1d5db;
            border-color: #374151;
        }

        .dark .historical-config-api-unavailable .panel-heading {
            background-color: #78350f;
            color: #fde68a;
            border-color: #92400e;
        }

        .dark .historical-config-api-unavailable pre {
            background-color: #0b1120;
            color: #d1d5db;
            border-color: #374151;
        }
    </style>

    @if(!($history['ok'] ?? false))
        <br>

        @if(!$apiOk)
            <div class="panel panel-warning historical-config-api-unavailable">
                <div class="panel-heading">
                    <strong>Historical Config is unavailable</strong>
                </div>
                <div class="panel-body">
                    @if($backendDriver === 'local')
                        <p>
                            The Local Git history backend is not healthy. Check that the Oxidized Git storage root is readable by LibreNMS:
                            <code>{{ $backendStorageRoot }}</code>.
                        </p>

                        <p>
                            Typical server checks:
                            <code>ls -ld {{ $backendStorageRoot }}</code>
                            and
                            <code>sudo -u librenms git --git-dir={{ rtrim($backendStorageRoot, '/') }}/&lt;group&gt;.git log -1</code>
                        </p>
                    @else
                        <p>
                            The History API is not reachable. Check that
                            <code>oxidized-history-api.service</code> is installed and running, and that LibreNMS can reach
                            <code>{{ $apiUrl }}</code>.
                        </p>

                        <p>
                            Typical server checks:
                            <code>systemctl status oxidized-history-api</code>
                            and
                            <code>curl {{ rtrim($apiUrl, '/') }}/health</code>
                        </p>
                    @endif

                    @if($apiError !== '' || $historyError !== '')
                        <details>
                            <summary>Technical details</summary>
                            <pre>{{ $apiError !== '' ? $apiError : $historyError }}</pre>
                        </details>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-warning">
                <strong>No historical config available.</strong>
                <br>
                {{ $historyError }}
                @if(isset($history['status']) && $history['status'])
                    <br>
                    HTTP status: {{ $history['status'] }}
                @endif
            </div>
        @endif
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
                            <strong>Plugin:</strong>
                            <span class="text-muted">{{ $pluginVersion }}</span>
                        </li>
                        <li class="list-group-item" style="overflow:hidden">
                            <strong>History backend:</strong>
                            @if($apiOk)
                                <span class="text-success">{{ $backendLabel }} ok</span>
                            @else
                                <span class="text-danger">{{ $backendLabel }} error</span>
                            @endif
                            @if($backendDriver === 'api' && !empty($apiPayload['version']))
                                <span class="text-muted">{{ $apiPayload['version'] }}</span>
                            @endif
                            @if($backendDebug)
                                <a class="pull-right" href="{{ request()->fullUrlWithQuery(['backend_debug' => 0]) }}">
                                    hide diagnostics
                                </a>
                            @else
                                <a class="pull-right" href="{{ request()->fullUrlWithQuery(['backend_debug' => 1]) }}">
                                    debug
                                </a>
                            @endif
                        </li>
                    </ul>
                </div>

                @if($backendDebug || !$apiOk)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Backend diagnostics:
                            @if($apiOk)
                                <strong>ok</strong>
                                <span class="label label-success pull-right">{{ $backendOkLabel }}</span>
                            @else
                                <strong>error</strong>
                                <span class="label label-danger pull-right">{{ $backendErrorLabel }}</span>
                            @endif
                        </div>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong>Backend:</strong>
                                {{ $backendLabel }}
                            </li>
                            <li class="list-group-item">
                                <strong>Service:</strong>
                                {{ $apiPayload['service'] ?? 'Unknown' }}
                            </li>
                            @if($backendDriver === 'api' || !empty($apiPayload['version']))
                                <li class="list-group-item">
                                    <strong>{{ $backendDriver === 'api' ? 'API version' : 'Version' }}:</strong>
                                    {{ $apiPayload['version'] ?? 'Unknown' }}
                                </li>
                            @endif
                            @if($backendDriver === 'api')
                                <li class="list-group-item">
                                    <strong>Auth:</strong>
                                    {{ ($apiConfig['auth_enabled'] ?? false) ? 'enabled' : 'disabled' }}
                                </li>
                            @endif
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
                                    <span class="text-muted">not reported</span>
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
            <div class="config historical-config-output">
                <input id="linenumbers" class="btn btn-primary" type="submit" value="Hide line numbers"/>
                {!! $renderedConfig !!}
            </div>
        @endif
    @endif
</x-device.page>
@endsection
