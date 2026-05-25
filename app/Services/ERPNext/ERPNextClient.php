<?php

namespace App\Services\ERPNext;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ERPNext v15 REST API client.
 * Authenticates via API Key + API Secret token header.
 */
class ERPNextClient
{
    protected PendingRequest $http;

    public function __construct()
    {
        $baseUrl = rtrim(config('erpnext.url'), '/');
        $token = $this->hasCredentials()
            ? config('erpnext.api_key').':'.config('erpnext.api_secret')
            : null;

        $this->http = Http::baseUrl($baseUrl.'/api')
            ->timeout(config('erpnext.timeout', 60))
            ->acceptJson()
            ->when($token, fn ($req) => $req->withHeaders([
                'Authorization' => 'token '.$token,
            ]));
    }

    public function hasCredentials(): bool
    {
        return (bool) config('erpnext.api_key') && (bool) config('erpnext.api_secret');
    }

    public function isConfigured(): bool
    {
        return $this->hasCredentials() && ! config('erpnext.use_dummy_data', true);
    }

    /**
     * @param  array<int, array<int, mixed>>  $filters
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function getList(string $doctype, array $filters = [], array $fields = ['*'], int $limit = 500): array
    {
        $params = [
            'fields' => json_encode($fields),
            'limit_page_length' => $limit,
        ];

        if (! empty($filters)) {
            $params['filters'] = json_encode(array_values($filters));
        }

        return $this->request('GET', '/resource/'.rawurlencode($doctype), $params)['data'] ?? [];
    }

    /**
     * @param  array<int, array<int, mixed>>  $filters
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function getListPaginated(string $doctype, array $filters = [], array $fields = ['*'], int $pageSize = 500, int $maxRows = 10000): array
    {
        $all = [];
        $start = 0;

        do {
            $params = [
                'fields' => json_encode($fields),
                'limit_page_length' => $pageSize,
                'limit_start' => $start,
            ];
            if (! empty($filters)) {
                $params['filters'] = json_encode(array_values($filters));
            }
            $chunk = $this->request('GET', '/resource/'.rawurlencode($doctype), $params)['data'] ?? [];
            $all = array_merge($all, $chunk);
            $start += $pageSize;
        } while (count($chunk) === $pageSize && $start < $maxRows);

        return $all;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDoc(string $doctype, string $name): array
    {
        return $this->request('GET', '/resource/'.rawurlencode($doctype).'/'.rawurlencode($name))['data'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return mixed
     */
    public function callMethod(string $method, array $params = []): mixed
    {
        $response = $this->request('POST', '/method/'.ltrim($method, '/'), $params);

        return $response['message'] ?? $response;
    }

    public function getAccountBalance(string $account, ?string $date = null, ?string $company = null): float
    {
        $balance = $this->callMethod('erpnext.accounts.utils.get_balance_on', [
            'account' => $account,
            'date' => $date ?? now()->toDateString(),
            'company' => $company ?? config('erpnext.default_company'),
        ]);

        return (float) $balance;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCompanies(): array
    {
        return $this->getList('Company', [], ['name', 'default_currency'], 50);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function request(string $method, string $uri, array $query = []): array
    {
        try {
            $response = match (strtoupper($method)) {
                'GET' => $this->http->get($uri, $query),
                'POST' => $this->http->asForm()->post($uri, $query),
                default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            $response->throw();

            return $response->json() ?? [];
        } catch (RequestException $e) {
            Log::error('ERPNext API error', [
                'uri' => $uri,
                'status' => $e->response?->status(),
                'body' => $e->response?->body(),
            ]);
            throw $e;
        }
    }
}
