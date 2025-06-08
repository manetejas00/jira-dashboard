<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraService
{
    protected string $baseUrl;
    protected string $email;
    protected string $apiToken;

    public function __construct()
    {
        $this->baseUrl = config('jira.site_url');
        $this->email = config('jira.email');
        $this->apiToken = config('jira.api_token');
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode("{$this->email}:{$this->apiToken}"),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function logError(string $message, $response, array $context = []): void
    {
        Log::error($message, array_merge([
            'status' => $response->status(),
            'body' => $response->body(),
        ], $context));
    }

    protected function handleResponseFailure(string $context, $response, $returnOnFail = null)
    {
        $this->logError("Failed to {$context}", $response);
        return $returnOnFail;
    }

    public function getTasks(string $projectKey): ?array
    {
        $url = "{$this->baseUrl}/rest/api/3/search";
        $jql = "project={$projectKey} ORDER BY priority DESC, created DESC";

        $response = Http::withHeaders($this->getAuthHeaders())
            ->get($url, [
                'jql' => $jql,
                'fields' => 'summary,assignee,project,status,created,priority,customfield_10020',
            ]);

        if ($response->failed()) {
            return $this->handleResponseFailure('fetch Jira tasks', $response, null);
        }

        return $response->json();
    }

    public function createTask(array $data): ?array
    {
        $url = "{$this->baseUrl}/rest/api/3/issue";

        Log::info('Sending Jira create issue request', [
            'url' => $url,
            'data' => $data,
        ]);

        $response = Http::withHeaders($this->getAuthHeaders())->post($url, $data);

        if ($response->failed()) {
            return $this->handleResponseFailure('create Jira task', $response, null);
        }

        Log::info('Jira task created successfully', [
            'response' => $response->json(),
        ]);

        return $response->json();
    }

    public function getAssignableUsers(string $projectKey): array
    {
        $url = "{$this->baseUrl}/rest/api/3/user/assignable/search";

        $response = Http::withHeaders($this->getAuthHeaders())
            ->get($url, ['project' => $projectKey, 'maxResults' => 50]);

        if ($response->failed()) {
            return $this->handleResponseFailure('fetch assignable users', $response, []);
        }

        return $response->json();
    }

    public function getPriorities(): array
    {
        $url = "{$this->baseUrl}/rest/api/3/priority";

        $response = Http::withHeaders($this->getAuthHeaders())->get($url);

        if ($response->failed()) {
            return $this->handleResponseFailure('fetch Jira priorities', $response, []);
        }

        return $response->json();
    }

    public function getStatuses(): array
    {
        $url = "{$this->baseUrl}/rest/api/3/status";

        $response = Http::withHeaders($this->getAuthHeaders())->get($url);

        if ($response->failed()) {
            return $this->handleResponseFailure('fetch Jira statuses', $response, []);
        }

        return $response->json();
    }

    public function updateTask(string $taskKey, array $data): ?array
    {
        $url = "{$this->baseUrl}/rest/api/3/issue/{$taskKey}";

        Log::info('Sending Jira update issue request', [
            'url' => $url,
            'data' => $data,
        ]);

        // Jira update is a PUT request without expecting a response body on success
        $response = Http::withHeaders($this->getAuthHeaders())->put($url, $data);

        if ($response->failed()) {
            return $this->handleResponseFailure('update Jira task', $response, null);
        }

        Log::info('Jira task updated successfully', [
            'taskKey' => $taskKey,
        ]);

        // Jira returns 204 No Content on success, so no body usually
        return ['success' => true];
    }

    public function getBoards(): array
    {
        $url = "{$this->baseUrl}/rest/agile/1.0/board";

        $response = Http::withHeaders($this->getAuthHeaders())->get($url);

        if ($response->failed()) {
            return $this->handleResponseFailure('fetch Jira boards', $response, []);
        }

        return $response->json();
    }
    public function getSprints(int $boardId): array
    {
        $url = "{$this->baseUrl}/rest/agile/1.0/board/{$boardId}/sprint";

        $response = Http::withHeaders($this->getAuthHeaders())->get($url);

        if ($response->failed()) {
            return $this->handleResponseFailure('fetch Jira sprints', $response, []);
        }

        return $response->json();
    }
}
