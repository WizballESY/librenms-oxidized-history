<?php

namespace WizballEsy\LibreNmsOxidizedHistory\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class HistoryApiClient
{
    /**
     * @return array{ok: bool, versions: array<int, array<string, mixed>>, status: int|null, error: string|null}
     */
    public function versions(string $nodeFull): array
    {
        try {
            $request = Http::acceptJson()
                ->timeout((float) config('oxidized-history.api_timeout', 2.0));

            $token = $this->apiToken();

            if (is_string($token) && $token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->get($this->baseUrl() . '/node/history.json', [
                'node_full' => $nodeFull,
            ]);

            if ($response->successful()) {
                $versions = $response->json();

                return [
                    'ok' => is_array($versions),
                    'versions' => is_array($versions) ? $versions : [],
                    'status' => $response->status(),
                    'error' => is_array($versions) ? null : 'Invalid JSON response from history API.',
                ];
            }

            return [
                'ok' => false,
                'versions' => [],
                'status' => $response->status(),
                'error' => $response->json('error') ?: $response->body(),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'versions' => [],
                'status' => null,
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * @return array{ok: bool, config: string|null, bytes: int|null, lines: int|null, status: int|null, error: string|null}
     */
    public function versionConfig(string $nodeFull, string $oid): array
    {
        if (! preg_match('/\A[0-9a-fA-F]{40}\z/', $oid)) {
            return [
                'ok' => false,
                'config' => null,
                'bytes' => null,
                'lines' => null,
                'status' => null,
                'error' => 'Invalid commit id.',
            ];
        }

        try {
            $request = Http::acceptJson()
                ->timeout((float) config('oxidized-history.api_timeout', 2.0));

            $token = $this->apiToken();

            if (is_string($token) && $token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->get($this->baseUrl() . '/node/history/view.json', [
                'node_full' => $nodeFull,
                'oid' => $oid,
                'include_config' => 1,
            ]);

            if ($response->successful()) {
                $payload = $response->json();

                return [
                    'ok' => is_array($payload) && array_key_exists('config', $payload),
                    'config' => is_array($payload) ? (string) ($payload['config'] ?? '') : null,
                    'bytes' => is_array($payload) ? (int) ($payload['bytes'] ?? 0) : null,
                    'lines' => is_array($payload) ? (int) ($payload['lines'] ?? 0) : null,
                    'status' => $response->status(),
                    'error' => is_array($payload) && array_key_exists('config', $payload)
                        ? null
                        : 'Invalid JSON response from history API.',
                ];
            }

            return [
                'ok' => false,
                'config' => null,
                'bytes' => null,
                'lines' => null,
                'status' => $response->status(),
                'error' => $response->json('error') ?: $response->body(),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'config' => null,
                'bytes' => null,
                'lines' => null,
                'status' => null,
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * @return array{ok: bool, files: array<int, array<string, mixed>>, status: int|null, error: string|null}
     */
    public function diff(string $nodeFull, string $oidNew, string $oidOld, bool $includePatch = true): array
    {
        foreach ([$oidNew, $oidOld] as $oid) {
            if (! preg_match('/\A[0-9a-fA-F]{40}\z/', $oid)) {
                return [
                    'ok' => false,
                    'files' => [],
                    'status' => null,
                    'error' => 'Invalid commit id.',
                ];
            }
        }

        try {
            $request = Http::acceptJson()
                ->timeout((float) config('oxidized-history.api_timeout', 2.0));

            $token = $this->apiToken();

            if (is_string($token) && $token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->get($this->baseUrl() . '/node/history/diffs.json', [
                'node_full' => $nodeFull,
                'oid' => $oidNew,
                'oid2' => $oidOld,
                'include_patch' => $includePatch ? 1 : 0,
            ]);

            if ($response->successful()) {
                $payload = $response->json();
                $files = is_array($payload) && isset($payload['files']) && is_array($payload['files'])
                    ? $payload['files']
                    : [];

                return [
                    'ok' => is_array($payload) && $files !== [],
                    'files' => $files,
                    'status' => $response->status(),
                    'error' => is_array($payload) && $files !== []
                        ? null
                        : 'Invalid JSON response from history API.',
                ];
            }

            return [
                'ok' => false,
                'files' => [],
                'status' => $response->status(),
                'error' => $response->json('error') ?: $response->body(),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'files' => [],
                'status' => null,
                'error' => $e->getMessage(),
            ];
        }
    }



    private function apiToken(): ?string
    {
        $tokenFile = config('oxidized-history.api_token_file');

        if (is_string($tokenFile) && $tokenFile !== '' && is_readable($tokenFile)) {
            $token = trim((string) file_get_contents($tokenFile));

            if ($token !== '') {
                return $token;
            }
        }

        $token = config('oxidized-history.api_token');

        return is_string($token) && $token !== '' ? $token : null;
    }


    private function baseUrl(): string
    {
        return rtrim((string) config('oxidized-history.api_url', 'http://127.0.0.1:8899'), '/');
    }
}
