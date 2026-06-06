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

            $token = config('oxidized-history.api_token');

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

    private function baseUrl(): string
    {
        return rtrim((string) config('oxidized-history.api_url', 'http://127.0.0.1:8899'), '/');
    }
}
