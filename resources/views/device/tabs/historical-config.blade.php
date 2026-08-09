@extends('layouts.librenmsv1')

@section('content')
<x-device.page :device="$device">
    @php
        $resolved = $data['resolved'] ?? [];
        $history = $data['history'] ?? [];
        $versions = $history['versions'] ?? [];

        $nodeFull = $resolved['node_full'] ?? null;
        $resolvedGroup = $resolved['group'] ?? null;

        $backendHealth = $data['backend_health'] ?? [];
        $backendPayload = is_array($backendHealth['payload'] ?? null)
            ? $backendHealth['payload']
            : [];
        $backendConfig = is_array($backendPayload['config'] ?? null)
            ? $backendPayload['config']
            : [];

        $backendLimits = is_array($backendConfig['limits'] ?? null)
            ? $backendConfig['limits']
            : [];

        $backendRepos = is_array($backendConfig['discovered_repositories'] ?? null)
            ? $backendConfig['discovered_repositories']
            : [];

        $backendOk = (bool) ($backendHealth['ok'] ?? false);
        $backendError = (string) ($backendHealth['error'] ?? '');
        $historyError = (string) ($history['error'] ?? 'Unknown error');

        $backendLabel = 'Local Git';
        $backendStorageRoot = (string) (
            $backendConfig['storage_root']
            ?? config('oxidized-history.git_storage_root', '/opt/librenms/.config/oxidized')
        );

        $pluginPackage = 'wizballesy/librenms-oxidized-history';
        $pluginVersion = 'unknown';

        if (class_exists(\Composer\InstalledVersions::class)) {
            $pluginVersion = \Composer\InstalledVersions::getPrettyVersion($pluginPackage) ?: 'dev';
        }

        $canBackendDebug = \Illuminate\Support\Facades\Gate::allows('admin');
        $backendDebug = $canBackendDebug
            && request()->boolean('backend_debug');

        $latestVersion = $versions[0] ?? null;
        $latestTime = is_array($latestVersion)
            ? ($latestVersion['time'] ?? $latestVersion['date'] ?? '')
            : '';

        $configUi = is_array($data['config_ui'] ?? null)
            ? $data['config_ui']
            : [];

        $latestTimestamp = $latestTime !== ''
            ? strtotime((string) $latestTime)
            : false;

        $configUi['initial_total'] = count($versions);
        $configUi['initial_latest_date'] = $latestTimestamp === false
            ? null
            : $latestTimestamp;
    @endphp

    @if(!($history['ok'] ?? false))
        <x-panel class="tw:mt-4" title="Historical Config">
            @if(!$backendOk)
                <div class="tw:rounded-lg tw:border tw:border-yellow-300 tw:bg-yellow-50 tw:p-4 tw:text-yellow-900
                            tw:dark:border-yellow-900 tw:dark:bg-yellow-900/30 tw:dark:text-yellow-200">
                    <strong>Historical Config is unavailable.</strong>

                    <p class="tw:mt-2 tw:mb-0">
                        The Local Git history backend is not healthy.
                    </p>

                    @if($backendError !== '' || $historyError !== '')
                        <details class="tw:mt-3">
                            <summary>Technical details</summary>
                            <pre class="tw:mt-2 tw:whitespace-pre-wrap tw:break-words">{{ $backendError !== '' ? $backendError : $historyError }}</pre>
                        </details>
                    @endif
                </div>
            @else
                <div class="tw:rounded-lg tw:border tw:border-yellow-300 tw:bg-yellow-50 tw:p-4 tw:text-yellow-900
                            tw:dark:border-yellow-900 tw:dark:bg-yellow-900/30 tw:dark:text-yellow-200">
                    <strong>No historical config available.</strong>
                    <div>{{ $historyError }}</div>
                </div>
            @endif
        </x-panel>
    @else
        <div
            x-data="historicalConfigBackups(@js($configUi))"
            data-config-backups
        >
        {{-- Plugin/backend status --}}
        <div id="historical-config-status" class="tw:mt-4">
            <x-panel title="Historical Config">
                <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-4">
                    <div class="tw:flex tw:flex-wrap tw:items-center tw:gap-4">
                        <span class="tw:whitespace-nowrap">
                            <strong>Node:</strong>
                            {{ $nodeFull ?: 'Not resolved' }}
                        </span>

                        <span class="tw:whitespace-nowrap">
                            <strong>Backups:</strong>
                            <span x-text="total">{{ count($versions) }}</span>
                        </span>

                        <span class="tw:whitespace-nowrap">
                            <strong>Last stored:</strong>
                            <span x-text="formatDate(latestBackupTimestamp)">{{ $latestTime }}</span>
                        </span>

                        <span class="tw:whitespace-nowrap">
                            <strong>Backend:</strong>
                            @if($backendOk)
                                <span class="tw:text-green-600 tw:dark:text-green-400">
                                    {{ $backendLabel }} ok
                                </span>
                            @else
                                <span class="tw:text-red-600 tw:dark:text-red-400">
                                    {{ $backendLabel }} error
                                </span>
                            @endif
                        </span>
                    </div>

                    @if($canBackendDebug)
                        <div class="tw:whitespace-nowrap">
                            @if($backendDebug)
                                <a href="{{ request()->fullUrlWithQuery(['backend_debug' => 0]) }}">
                                    hide diagnostics
                                </a>
                            @else
                                <a href="{{ request()->fullUrlWithQuery(['backend_debug' => 1]) }}">
                                    debug
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </x-panel>
        </div>

        @if($canBackendDebug && ($backendDebug || !$backendOk))
            <div class="tw:mt-4">
                <x-panel title="Backend diagnostics">
                    <dl class="tw:m-0">
                        <dt>Oxidized group</dt>
                        <dd>{{ $resolvedGroup ?: 'not resolved' }}</dd>

                        <dt class="tw:mt-2">LibreNMS OS</dt>
                        <dd>{{ $device->os }}</dd>

                        <dt class="tw:mt-2">Plugin version</dt>
                        <dd>{{ $pluginVersion }}</dd>

                        <dt class="tw:mt-2">Git storage root</dt>
                        <dd><code>{{ $backendStorageRoot }}</code></dd>

                        <dt class="tw:mt-2">Git repo lookup</dt>
                        <dd>by Oxidized group</dd>

                        <dt class="tw:mt-2">Limits</dt>
                        <dd>
                            @if(count($backendLimits) > 0)
                                {{ $backendLimits['max_versions'] ?? '?' }} versions /
                                {{ $backendLimits['max_config_bytes'] ?? '?' }} bytes
                            @else
                                not reported
                            @endif
                        </dd>

                        <dt class="tw:mt-2">Detected backup repos</dt>
                        <dd>
                            @if(count($backendRepos) > 0)
                                {{ implode(', ', $backendRepos) }}
                            @else
                                not reported
                            @endif
                        </dd>

                        @if(!$backendOk)
                            <dt class="tw:mt-2">Error</dt>
                            <dd class="tw:text-red-600 tw:dark:text-red-400">
                                {{ $backendHealth['error'] ?? 'Unknown error' }}
                            </dd>
                        @endif
                    </dl>
                </x-panel>
            </div>
        @endif

        {{-- LibreNMS-style backup/config UI --}}
        <div
            id="historical-config-main"
            class="tw:mt-4 tw:flex tw:flex-col tw:lg:flex-row tw:gap-4 tw:items-start"
        >
            {{-- Backup list --}}
            <x-panel class="tw:w-full tw:lg:w-md tw:lg:shrink-0 tw:overflow-hidden tw:self-start tw:mb-0!">
                <x-slot name="heading" class="tw:flex tw:items-center tw:justify-between">
                    <h3 class="panel-title">
                        Backups
                        <span
                            x-show="!loadingBackups"
                            x-cloak
                            class="tw:font-normal tw:text-gray-500 tw:dark:text-dark-white-400"
                            x-text="'(' + total + ')'"
                        ></span>
                    </h3>

                    <button
                        type="button"
                        x-show="total > 1"
                        x-cloak
                        x-on:click="toggleDiffMode()"
                        x-text="diffMode ? 'Show Config' : 'Show Diff'"
                        class="lnms-btn lnms-btn-primary tw:transition-colors"
                    ></button>
                </x-slot>

                <x-slot name="table">
                    <p
                        x-show="diffMode"
                        x-cloak
                        class="tw:px-4 tw:py-2 tw:m-0 tw:text-gray-500 tw:dark:text-dark-white-400
                               tw:border-b tw:border-gray-200 tw:dark:border-dark-gray-200"
                    >
                        Select two backups to compare.
                    </p>

                    <div
                        x-show="loadingBackups"
                        x-cloak
                        class="tw:py-6 tw:text-center tw:text-gray-500 tw:dark:text-dark-white-400"
                    >
                        <i class="fa fa-spinner tw:animate-spin"></i>
                    </div>

                    <ul
                        x-show="!loadingBackups"
                        :style="{ maxHeight: viewportMaxHeight }"
                        class="tw:list-none tw:m-0 tw:p-0 tw:divide-y tw:divide-gray-200
                               tw:dark:divide-dark-gray-200 tw:max-h-60 tw:lg:max-h-[70vh]
                               tw:overflow-y-auto"
                    >
                        <template x-for="backup in backups" :key="backup.id">
                            <li>
                                <button
                                    type="button"
                                    x-on:click="selectBackup(backup)"
                                    :class="isSelected(backup)
                                        ? 'tw:bg-blue-50 tw:dark:bg-blue-900/40'
                                        : 'tw:hover:bg-gray-50 tw:dark:hover:bg-dark-gray-300'"
                                    class="tw:w-full tw:text-left tw:px-4 tw:py-2.5 tw:flex
                                           tw:items-center tw:gap-2 tw:transition-colors"
                                >
                                    <template x-if="diffMode">
                                        <span
                                            :class="isSelected(backup)
                                                ? 'tw:bg-blue-600 tw:border-blue-600'
                                                : 'tw:border-gray-400 tw:dark:border-dark-gray-100'"
                                            class="tw:inline-block tw:w-4 tw:h-4 tw:shrink-0
                                                   tw:rounded tw:border-2"
                                        ></span>
                                    </template>

                                    <span class="tw:flex-1">
                                        <span
                                            class="tw:block tw:text-gray-800 tw:dark:text-dark-white-100"
                                            x-text="formatDate(backup.date)"
                                        ></span>
                                    </span>

                                    <template x-if="diffMode && diffRole(backup)">
                                        <span
                                            :class="diffRole(backup) === 'old'
                                                ? 'tw:bg-red-100 tw:text-red-800 tw:dark:bg-red-900/40 tw:dark:text-red-300'
                                                : 'tw:bg-green-100 tw:text-green-800 tw:dark:bg-green-900/40 tw:dark:text-green-300'"
                                            class="tw:text-xs tw:font-medium tw:rounded tw:px-1.5 tw:py-0.5"
                                            x-text="diffRole(backup) === 'old' ? 'Old' : 'New'"
                                        ></span>
                                    </template>
                                </button>
                            </li>
                        </template>
                    </ul>
                </x-slot>
            </x-panel>

            {{-- Config / diff --}}
            <x-panel class="tw:w-full tw:flex-1 tw:min-w-0 tw:overflow-hidden tw:self-start tw:mb-0!">
                <x-slot name="heading" class="tw:flex tw:items-center tw:justify-between">
                    <h3 class="panel-title">
                        <span x-show="diffMode">
                            Diff<span x-show="diffSelection.length === 2">:
                                <span x-text="formatDate(Math.min(diffSelection[0]?.date, diffSelection[1]?.date))"></span>
                                <i class="fa fa-arrow-right tw:mx-1 tw:text-xs tw:align-middle" aria-hidden="true"></i>
                                <span x-text="formatDate(Math.max(diffSelection[0]?.date, diffSelection[1]?.date))"></span>
                            </span>
                        </span>

                        <span x-show="!diffMode">
                            Configuration<span
                                x-show="selected"
                                x-text="': ' + formatDate(selected?.date)"
                            ></span>
                        </span>
                    </h3>

                    <div
                        x-show="showConfigView"
                        x-cloak
                        class="tw:flex tw:items-center tw:gap-2"
                    >
                        <button
                            type="button"
                            x-show="canTakeBackup"
                            x-cloak
                            x-on:click="takeBackup()"
                            :disabled="takingBackup"
                            class="lnms-btn lnms-btn-primary tw:flex tw:items-center tw:gap-1.5"
                        >
                            <i
                                class="fa"
                                :class="backupProgressIcon()"
                                aria-hidden="true"
                            ></i>
                            <span x-text="backupProgressText()"></span>
                        </button>

                        <button
                            type="button"
                            x-on:click="downloadConfig()"
                            class="lnms-btn lnms-btn-default tw:flex tw:items-center tw:gap-1.5"
                        >
                            <i class="fa fa-download" aria-hidden="true"></i>
                            <span>Download</span>
                        </button>

                        <button
                            type="button"
                            x-on:click="copyConfig()"
                            class="lnms-btn lnms-btn-default tw:flex tw:items-center tw:gap-1.5"
                        >
                            <i
                                class="fa"
                                :class="copied
                                    ? 'fa-check tw:text-green-600 tw:dark:text-green-400'
                                    : 'fa-copy'"
                                aria-hidden="true"
                            ></i>
                            <span x-text="copied ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
                </x-slot>

                <div
                    x-show="error"
                    x-cloak
                    class="tw:mb-3 tw:rounded-lg tw:border tw:border-red-300 tw:bg-red-50
                           tw:text-red-800 tw:dark:border-red-900 tw:dark:bg-red-900/30
                           tw:dark:text-red-300 tw:px-4 tw:py-3 tw:text-sm"
                    x-text="errorMessage()"
                ></div>

                <div
                    x-show="showSpinner"
                    x-cloak
                    class="tw:py-10 tw:text-center tw:text-gray-500 tw:dark:text-dark-white-400"
                >
                    <i class="fa fa-spinner tw:animate-spin fa-2x"></i>
                </div>

                {{-- Diff --}}
                <template x-if="showDiffView">
                    <div
                        :style="{ maxHeight: viewportMaxHeight }"
                        class="tw:rounded-lg tw:overflow-x-auto tw:max-h-[70vh]
                               tw:overflow-y-auto tw:border tw:border-gray-200
                               tw:dark:border-dark-gray-200"
                    >
                        <table class="tw:w-full tw:m-0 tw:font-mono tw:border-collapse">
                            <tbody class="tw:align-text-top">
                                <template x-for="(row, index) in diffRows" :key="index">
                                    <tr
                                        :class="{
                                            'tw:bg-green-100 tw:dark:bg-green-900/40': row.mode === 'added',
                                            'tw:bg-red-100 tw:dark:bg-red-900/40': row.mode === 'removed'
                                        }"
                                    >
                                        <td
                                            class="tw:w-12 tw:px-2 tw:py-0.5 tw:text-right tw:select-none
                                                   tw:text-gray-400 tw:dark:text-dark-white-400 tw:border-r
                                                   tw:border-gray-200 tw:dark:border-dark-gray-200"
                                            x-text="row.line ?? ''"
                                        ></td>

                                        <td
                                            class="tw:w-6 tw:px-1 tw:py-0.5 tw:text-center tw:select-none"
                                            :class="{
                                                'tw:text-green-700 tw:dark:text-green-400': row.mode === 'added',
                                                'tw:text-red-700 tw:dark:text-red-400': row.mode === 'removed'
                                            }"
                                            x-text="row.mode === 'added'
                                                ? '+'
                                                : (row.mode === 'removed' ? '-' : '')"
                                        ></td>

                                        <td
                                            class="tw:px-2 tw:py-0.5 tw:whitespace-pre-wrap
                                                   tw:text-gray-800 tw:dark:text-dark-white-100"
                                            x-text="row.text"
                                        ></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                <p
                    x-show="showDiffPrompt"
                    x-cloak
                    class="tw:py-10 tw:m-0 tw:text-center tw:text-gray-500 tw:dark:text-dark-white-400"
                >
                    Select two backups to compare.
                </p>

                {{-- Config --}}
                <template x-if="showConfigView">
                    <pre
                        :style="{ maxHeight: viewportMaxHeight }"
                        class="config-highlight line-numbers tw:m-0 tw:p-3 tw:font-mono
                               tw:whitespace-pre-wrap tw:overflow-x-auto tw:max-h-[70vh]
                               tw:overflow-y-auto tw:rounded-lg tw:bg-gray-50 tw:text-gray-800
                               tw:dark:bg-dark-gray-500 tw:dark:text-dark-white-200 tw:border
                               tw:border-gray-200 tw:dark:border-dark-gray-200"
                    ><code
                        x-config-highlight="selected.content"
                        data-os="{{ $configUi['os'] ?? $device->os }}"
                        data-config-highlighting="{{ $configUi['config_highlighting'] ?? '' }}"
                    ></code></pre>
                </template>
            </x-panel>
        </div>
        </div>
    @endif
</x-device.page>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    window.Alpine.directive('config-highlight', (element, { expression }, { effect, evaluateLater }) => {
        const evaluateContent = evaluateLater(expression);
        let updateId = 0;

        effect(() => {
            evaluateContent((content) => {
                const currentUpdateId = ++updateId;

                window.LibreNMS.loadConfigHighlight().then(({ default: highlightConfig }) => {
                    if (currentUpdateId === updateId) {
                        highlightConfig(element, content);
                    }
                });
            });
        });
    });

    window.Alpine.data('historicalConfigBackups', (config) => ({
        backups: [],
        total: Number(config.initial_total ?? 0),
        latestBackupTimestamp: config.initial_latest_date ?? null,

        selected: null,

        loadingBackups: false,
        loading: false,
        showSpinner: false,

        error: null,
        copied: false,

        canTakeBackup: Boolean(config.can_take_backup),
        takingBackup: false,
        backupProgress: null,

        viewportMaxHeight: '70vh',
        resizeHandler: null,
        statusResizeObserver: null,

        diffMode: false,
        diffSelection: [],
        diffGroups: null,
        diffRequestId: 0,

        urls: config.urls || {},
        messages: config.messages || {},

        async init() {
            this.resizeHandler = () => this.updateViewportMaxHeight();

            window.addEventListener('resize', this.resizeHandler);

            const status = document.getElementById('historical-config-status');

            if (status && typeof ResizeObserver !== 'undefined') {
                this.statusResizeObserver = new ResizeObserver(
                    this.resizeHandler
                );

                this.statusResizeObserver.observe(status);
            }

            this.$nextTick(() => {
                this.updateViewportMaxHeight();
            });

            await this.loadLatest();
            await this.loadBackups();
        },

        destroy() {
            if (this.resizeHandler) {
                window.removeEventListener(
                    'resize',
                    this.resizeHandler
                );
            }

            if (this.statusResizeObserver) {
                this.statusResizeObserver.disconnect();
            }
        },

        updateViewportMaxHeight() {
            const status = document.getElementById(
                'historical-config-status'
            );

            const main = document.getElementById(
                'historical-config-main'
            );

            /*
             * Native LibreNMS gives the config area 70vh.
             *
             * Measure the actual distance from the top of our status area
             * to the main Backups/Configuration area. This automatically
             * includes the status panel, diagnostics when visible, and all
             * vertical gaps between them.
             */
            const contentOffset = status && main
                ? Math.max(
                    0,
                    main.getBoundingClientRect().top
                        - status.getBoundingClientRect().top
                )
                : 0;

            const nativeHeight = window.innerHeight * 0.70;

            this.viewportMaxHeight =
                Math.max(
                    240,
                    Math.floor(nativeHeight - contentOffset)
                ) + 'px';
        },

        beginLoading() {
            this.loading = true;
            this.showSpinner = false;

            return setTimeout(() => {
                if (this.loading) {
                    this.showSpinner = true;
                }
            }, 300);
        },

        finishLoading(timer) {
            clearTimeout(timer);
            this.loading = false;
            this.showSpinner = false;
        },

        async loadBackups() {
            this.loadingBackups = true;

            try {
                const { data } = await window.axios.get(this.urls.backups);

                this.backups = Array.isArray(data.backups)
                    ? data.backups
                    : [];

                this.total = Number(data.total ?? this.backups.length);
                this.latestBackupTimestamp =
                    this.backups[0]?.date ?? this.latestBackupTimestamp;
            } catch (error) {
                this.error = this.requestError(error);
            } finally {
                this.loadingBackups = false;
            }
        },

        async loadLatest() {
            const timer = this.beginLoading();

            try {
                const { data } = await window.axios.get(this.urls.backup);

                this.selected = data;
                this.error = null;
            } catch (error) {
                this.error = this.requestError(error);
            } finally {
                this.finishLoading(timer);
            }
        },

        async loadBackupContent(backup) {
            const timer = this.beginLoading();

            try {
                const { data } = await window.axios.get(
                    this.urls.backup,
                    {
                        params: {
                            backup: backup.id
                        }
                    }
                );

                if (this.selected?.id === backup.id) {
                    this.selected.content = data.content;
                    this.error = null;
                }
            } catch (error) {
                if (this.selected?.id === backup.id) {
                    this.selected.content = null;
                    this.error = this.requestError(error);
                }
            } finally {
                this.finishLoading(timer);
            }
        },

        selectBackup(backup) {
            if (this.diffMode) {
                this.toggleDiffSelection(backup);
                return;
            }

            if (
                this.selected?.id === backup.id
                && this.selected?.content != null
            ) {
                return;
            }

            this.selected = {
                ...backup,
                content: null
            };

            this.error = null;
            this.loadBackupContent(backup);
        },

        toggleDiffMode() {
            this.diffMode = !this.diffMode;
            this.error = null;

            if (this.diffMode) {
                this.enterDiffMode();
            } else {
                this.exitDiffMode();
            }
        },

        enterDiffMode() {
            const textBackups = this.backups.filter(
                backup => backup.type === 'TEXT'
            );

            const selectedIndex = textBackups.findIndex(
                backup => backup.id === this.selected?.id
            );

            if (selectedIndex !== -1) {
                const adjacent =
                    textBackups[selectedIndex + 1]
                    ?? textBackups[selectedIndex - 1];

                this.diffSelection = adjacent
                    ? [textBackups[selectedIndex], adjacent]
                    : [textBackups[selectedIndex]];
            } else {
                this.diffSelection = textBackups.slice(0, 2);
            }

            if (this.diffSelection.length === 2) {
                this.loadDiff();
            } else {
                this.diffGroups = null;
            }
        },

        exitDiffMode() {
            const target = this.diffSelection[0] ?? null;

            this.diffRequestId++;
            this.diffSelection = [];
            this.diffGroups = null;

            if (
                target
                && (
                    this.selected?.id !== target.id
                    || this.selected?.content == null
                )
            ) {
                this.selectBackup(target);
            }
        },

        toggleDiffSelection(backup) {
            const index = this.diffSelection.findIndex(
                item => item.id === backup.id
            );

            if (index !== -1) {
                this.diffSelection.splice(index, 1);
                this.diffGroups = null;
                return;
            }

            if (this.diffSelection.length === 0) {
                this.diffSelection = [backup];
            } else if (this.diffSelection.length === 1) {
                this.diffSelection.push(backup);
            } else {
                this.diffSelection.splice(1, 1, backup);
            }

            if (this.diffSelection.length === 2) {
                this.loadDiff();
            } else {
                this.diffGroups = null;
            }
        },

        get sortedDiff() {
            if (this.diffSelection.length !== 2) {
                return null;
            }

            const [first, second] = this.diffSelection;

            return first.date <= second.date
                ? { orig: first, rev: second }
                : { orig: second, rev: first };
        },

        get diffRoles() {
            if (!this.diffMode || !this.sortedDiff) {
                return {};
            }

            return {
                [this.sortedDiff.orig.id]: 'old',
                [this.sortedDiff.rev.id]: 'new'
            };
        },

        diffRole(backup) {
            return this.diffRoles[backup.id] ?? null;
        },

        async loadDiff() {
            if (!this.sortedDiff) {
                return;
            }

            const requestId = ++this.diffRequestId;
            const timer = this.beginLoading();
            const { orig, rev } = this.sortedDiff;

            this.diffGroups = null;
            this.error = null;

            try {
                const { data } = await window.axios.get(
                    this.urls.diff,
                    {
                        params: {
                            orig: orig.id,
                            rev: rev.id
                        }
                    }
                );

                if (requestId !== this.diffRequestId) {
                    return;
                }

                this.diffGroups = Array.isArray(data.groups)
                    ? data.groups
                    : [];
            } catch (error) {
                if (requestId !== this.diffRequestId) {
                    return;
                }

                this.error = this.requestError(error);
            } finally {
                /*
                 * An older request must not hide the spinner belonging to
                 * a newer diff request.
                 */
                if (requestId === this.diffRequestId) {
                    this.finishLoading(timer);
                } else {
                    clearTimeout(timer);
                }
            }
        },

        get diffRows() {
            if (!this.diffGroups) {
                return [];
            }

            const rows = [];

            const append = (mode, lines) => {
                (lines || []).forEach((line) => {
                    rows.push({
                        mode,
                        line: line.line,
                        text: line.text
                    });
                });
            };

            this.diffGroups.forEach((group) => {
                if (group.type === 'COMMON') {
                    append('common', group.original);
                    return;
                }

                if (
                    group.type === 'DELETED'
                    || group.type === 'CHANGED'
                ) {
                    append('removed', group.original);
                }

                if (
                    group.type === 'INSERTED'
                    || group.type === 'CHANGED'
                ) {
                    append('added', group.revised);
                }
            });

            return rows;
        },

        isSelected(backup) {
            if (this.diffMode) {
                return this.diffSelection.some(
                    item => item.id === backup.id
                );
            }

            return this.selected?.id === backup.id;
        },

        get showDiffView() {
            return (
                !this.showSpinner
                && this.diffMode
                && this.diffSelection.length === 2
                && this.diffGroups !== null
            );
        },

        get showDiffPrompt() {
            return (
                !this.showSpinner
                && this.diffMode
                && !this.showDiffView
                && !this.error
            );
        },

        get showConfigView() {
            return (
                !this.showSpinner
                && !this.diffMode
                && this.selected?.content != null
            );
        },

        formatDate(timestamp) {
            if (!timestamp) {
                return '';
            }

            if (window.LibreNMS?.Date?.display) {
                return window.LibreNMS.Date.display(timestamp);
            }

            return new Date(timestamp * 1000).toLocaleString();
        },

        requestError(error) {
            return error?.response?.data?.error ?? 'request_failed';
        },

        errorMessage() {
            return (
                this.messages[this.error]
                ?? this.messages.request_failed
                ?? this.error
                ?? 'The request failed.'
            );
        },

        backupProgressText() {
            switch (this.backupProgress) {
                case 'queueing':
                    return 'Queuing...';

                case 'waiting':
                    return 'Waiting for Oxidized...';

                case 'complete':
                    return 'Backup complete';

                case 'unchanged':
                    return 'Backup complete · No new revision';

                case 'failed':
                    return 'Backup failed';

                default:
                    return 'Take Backup';
            }
        },

        backupProgressIcon() {
            switch (this.backupProgress) {
                case 'queueing':
                case 'waiting':
                    return 'fa-spinner tw:animate-spin';

                case 'complete':
                    return 'fa-check';

                case 'unchanged':
                    return 'fa-info-circle';

                case 'failed':
                    return 'fa-exclamation-triangle';

                default:
                    return 'fa-refresh';
            }
        },

        resetBackupProgressAfter(delay = 5000) {
            const currentProgress = this.backupProgress;

            setTimeout(() => {
                if (this.backupProgress === currentProgress) {
                    this.backupProgress = null;
                }
            }, delay);
        },

        async takeBackup() {
            if (!this.canTakeBackup || this.takingBackup) {
                return;
            }

            const previousLatestId = this.backups[0]?.id ?? null;

            this.takingBackup = true;
            this.backupProgress = 'queueing';
            this.error = null;

            try {
                /*
                 * The queue request also returns Oxidized's last completed
                 * job before this backup was queued. This avoids a separate
                 * pre-flight status request and guarantees that node lookup
                 * and queueing use the same Oxidized node.
                 */
                const { data: queueResponse } = await window.axios.post(
                    this.urls.take_backup
                );

                const previousLastEnd =
                    queueResponse.baseline?.last_end ?? null;

                this.backupProgress = 'waiting';

                /*
                 * Oxidized does not provide a job ID here, so watch last_end.
                 * Normally this completes quickly. The two-minute limit is
                 * only a safety timeout for a busy or stuck queue.
                 */
                for (let attempt = 0; attempt < 60; attempt++) {
                    await new Promise(resolve => setTimeout(resolve, 2000));

                    const { data: status } = await window.axios.get(
                        this.urls.backup_status
                    );

                    const lastEnd = status.last_end ?? null;

                    if (
                        !lastEnd
                        || lastEnd === previousLastEnd
                    ) {
                        continue;
                    }

                    /*
                     * A changed last_end means Oxidized completed a run after
                     * our baseline. A non-success status is a real failure.
                     */
                    if (status.last_status !== 'success') {
                        this.error = 'backup_failed';
                        this.backupProgress = 'failed';
                        this.resetBackupProgressAfter();

                        return;
                    }

                    /*
                     * Oxidized updates last_end just before its output backend
                     * has necessarily finished storing the result. Give Local
                     * Git a short grace period before deciding that no new
                     * revision was created.
                     */
                    for (let gitAttempt = 0; gitAttempt < 6; gitAttempt++) {
                        if (gitAttempt > 0) {
                            await new Promise(
                                resolve => setTimeout(resolve, 500)
                            );
                        }

                        const { data } = await window.axios.get(
                            this.urls.backups
                        );

                        const refreshedBackups = Array.isArray(data.backups)
                            ? data.backups
                            : [];

                        this.backups = refreshedBackups;
                        this.total = Number(
                            data.total ?? refreshedBackups.length
                        );

                        this.latestBackupTimestamp =
                            refreshedBackups[0]?.date
                            ?? this.latestBackupTimestamp;

                        const newestBackup = refreshedBackups[0] ?? null;

                        if (
                            newestBackup
                            && newestBackup.id !== previousLatestId
                        ) {
                            this.backupProgress = 'complete';

                            this.selectBackup(newestBackup);
                            this.resetBackupProgressAfter();

                            return;
                        }
                    }

                    /*
                     * The Oxidized run completed successfully, but Local Git
                     * did not expose a new revision during the grace period.
                     */
                    this.backupProgress = 'unchanged';
                    this.resetBackupProgressAfter();

                    return;
                }

                this.error = 'backup_status_timeout';
                this.backupProgress = 'failed';
                this.resetBackupProgressAfter();
            } catch (error) {
                this.error = this.requestError(error);
                this.backupProgress = 'failed';
                this.resetBackupProgressAfter();
            } finally {
                this.takingBackup = false;
            }
        },

        downloadConfig() {
            if (!this.selected?.content) {
                return;
            }

            const date = this.selected.date
                ? new Date(this.selected.date * 1000)
                    .toISOString()
                    .split('T')[0]
                : 'latest';

            const hostname = config.hostname
                ? `${config.hostname}-`
                : '';

            const blob = new Blob(
                [this.selected.content],
                { type: 'text/plain;charset=utf-8' }
            );

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = `${hostname}config-${date}.txt`;
            link.click();

            URL.revokeObjectURL(url);
        },

        copyConfig() {
            if (!this.selected?.content) {
                return;
            }

            const content = this.selected.content;

            const copiedSuccessfully = () => {
                this.copied = true;
                this.error = null;

                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            };

            const fallbackCopy = () => {
                const textarea = document.createElement('textarea');

                textarea.value = content;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.top = '0';
                textarea.style.left = '0';
                textarea.style.opacity = '0';

                document.body.appendChild(textarea);

                textarea.focus();
                textarea.select();
                textarea.setSelectionRange(0, textarea.value.length);

                let copied = false;

                try {
                    copied = document.execCommand('copy');
                } finally {
                    textarea.remove();
                }

                if (copied) {
                    copiedSuccessfully();
                } else {
                    this.error = 'request_failed';
                }
            };

            if (
                window.isSecureContext
                && navigator.clipboard
                && typeof navigator.clipboard.writeText === 'function'
            ) {
                navigator.clipboard
                    .writeText(content)
                    .then(copiedSuccessfully)
                    .catch(fallbackCopy);

                return;
            }

            fallbackCopy();
        }
    }));
});
</script>
@endpush
