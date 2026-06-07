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
                            <strong>IP:</strong> {{ $device->ip }}
                        </li>
                        <li class="list-group-item">
                            <strong>Model:</strong> {{ strtoupper((string) $device->os) }}
                        </li>
                        <li class="list-group-item" style="overflow:hidden">
                            <strong>Last Stored:</strong> {{ $latestTime }}
                            <span class="label label-primary pull-right">
                                {{ count($versions) }} versions
                            </span>
                        </li>
                    </ul>
                </div>
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
