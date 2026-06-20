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

        $backendHealth = $data['backend_health'] ?? [];
        $backendPayload = is_array($backendHealth['payload'] ?? null) ? $backendHealth['payload'] : [];
        $backendConfig = is_array($backendPayload['config'] ?? null) ? $backendPayload['config'] : [];
        $backendLimits = is_array($backendConfig['limits'] ?? null) ? $backendConfig['limits'] : [];
        $backendRepos = is_array($backendConfig['discovered_repositories'] ?? null) ? $backendConfig['discovered_repositories'] : [];
        $backendOk = (bool) ($backendHealth['ok'] ?? false);
        $backendError = (string) ($backendHealth['error'] ?? '');
        $historyError = (string) ($history['error'] ?? 'Unknown error');

        $backendDriver = strtolower((string) ($backendPayload['driver'] ?? 'local'));
        $backendLabel = 'Local Git';
        $backendOkLabel = 'healthy';
        $backendErrorLabel = 'unhealthy';
        $backendStorageRoot = (string) ($backendConfig['storage_root'] ?? config('oxidized-history.git_storage_root', '/opt/librenms/.config/oxidized'));

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

        @if(!$backendOk)
            <div class="panel panel-warning historical-config-api-unavailable">
                <div class="panel-heading">
                    <strong>Historical Config is unavailable</strong>
                </div>
                <div class="panel-body">
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

                    @if($backendError !== '' || $historyError !== '')
                        <details>
                            <summary>Technical details</summary>
                            <pre>{{ $backendError !== '' ? $backendError : $historyError }}</pre>
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
                            @if($backendOk)
                                <span class="text-success">{{ $backendLabel }} ok</span>
                            @else
                                <span class="text-danger">{{ $backendLabel }} error</span>
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

                @if($backendDebug || !$backendOk)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Backend diagnostics:
                            @if($backendOk)
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
                                {{ $backendPayload['service'] ?? 'Unknown' }}
                            </li>
                            <li class="list-group-item">
                                <strong>Limits:</strong>
                                @if(count($backendLimits) > 0)
                                    {{ $backendLimits['max_versions'] ?? '?' }} versions /
                                    {{ $backendLimits['max_config_bytes'] ?? '?' }} bytes
                                @else
                                    <span class="text-muted">not reported</span>
                                @endif
                            </li>
                            <li class="list-group-item">
                                <strong>Detected backup repos:</strong>
                                @if(count($backendRepos) > 0)
                                    {{ implode(', ', $backendRepos) }}
                                @else
                                    <span class="text-muted">not reported</span>
                                @endif
                            </li>
                            @if(!$backendOk)
                                <li class="list-group-item text-danger">
                                    <strong>Error:</strong>
                                    {{ $backendHealth['error'] ?? 'Unknown error' }}
                                    @if(!empty($backendHealth['status']))
                                        <span class="text-muted">HTTP {{ $backendHealth['status'] }}</span>
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
