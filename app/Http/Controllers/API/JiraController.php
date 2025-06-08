<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\JiraService;
use Illuminate\Support\Facades\Log;

class JiraController extends Controller
{
    protected $jiraService;

    public function __construct(JiraService $jiraService)
    {
        $this->jiraService = $jiraService;
    }

    public function getTasks()
    {
        $projectKey = 'SCRUM';

        Log::info("Fetching Jira tasks for project: {$projectKey}");

        $tasks = $this->jiraService->getTasks($projectKey);

        if (!$tasks) {
            Log::error("Failed to fetch Jira tasks for project: {$projectKey}");
            return response()->json(['error' => 'Failed to fetch Jira tasks'], 500);
        }

        Log::info("Successfully fetched Jira tasks", ['tasks_count' => count($tasks)]);

        return response()->json($tasks);
    }

    // Example controller method that receives frontend payload and sends to Jira
    public function createTask(Request $request)
    {
        $data = $request->validate([
            'summary' => 'required|string',
            'description' => 'nullable|string',
            'labels' => 'nullable|array',
            'assignee' => 'nullable|array',
            'priority' => 'nullable|string',
            'status' => 'nullable|string',
            'sprint' => 'nullable|integer',
        ]);

        // Convert sprint to int if possible, else null
        $sprintId = is_numeric($data['sprint'] ?? null) ? (int) $data['sprint'] : null;

        $jiraPayload = [
            'fields' => [
                'project' => ['key' => 'SCRUM'], // Adjust project key if needed
                'summary' => $data['summary'],
                'description' => [
                    'type' => 'doc',
                    'version' => 1,
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                ['type' => 'text', 'text' => $data['description'] ?? ''],
                            ],
                        ],
                    ],
                ],
                'issuetype' => ['name' => 'Task'],
                'labels' => $data['labels'] ?? [],
                'assignee' => isset($data['assignee']['accountId']) ? ['accountId' => $data['assignee']['accountId']] : null,
                'priority' => $data['priority'] ? ['id' => $data['priority']] : null,
                // 'status' is usually not settable on create, you might need to omit or handle differently
            ],
        ];

        if ($sprintId) {
            $jiraPayload['fields']['customfield_10020'] = $sprintId;
        }

        // Clean null fields
        $jiraPayload['fields'] = array_filter($jiraPayload['fields'], fn($v) => $v !== null);

        // Call Jira API using your service (pseudo-code)
        $response = $this->jiraService->createTask($jiraPayload);

        return response()->json($response);
    }

    public function getAssignableUsers()
    {
        $projectKey = 'SCRUM';

        Log::info("Fetching assignable users for project: {$projectKey}");

        $users = $this->jiraService->getAssignableUsers($projectKey);

        if (!$users) {
            Log::error("Failed to fetch assignable users for project: {$projectKey}");
            return response()->json(['error' => 'Failed to fetch assignable users'], 500);
        }

        return response()->json($users);
    }

    public function getPriorities()
    {
        Log::info("Fetching Jira priorities");

        $priorities = $this->jiraService->getPriorities();

        if (!$priorities) {
            Log::error("Failed to fetch Jira priorities");
            return response()->json(['error' => 'Failed to fetch priorities'], 500);
        }

        return response()->json($priorities);
    }

    public function getStatuses()
    {
        Log::info("Fetching Jira statuses");

        $statuses = $this->jiraService->getStatuses();

        if (!$statuses) {
            Log::error("Failed to fetch Jira statuses");
            return response()->json(['error' => 'Failed to fetch statuses'], 500);
        }

        return response()->json($statuses);
    }

    public function updateTask(Request $request, string $taskKey)
    {
        $data = $request->validate([
            'summary' => 'sometimes|string',
            'description' => 'sometimes|string|nullable',
            'labels' => 'sometimes|array',
            'assignee' => 'sometimes|array|nullable',
            'priority' => 'sometimes|string|nullable',
            'status' => 'sometimes|string|nullable',
            'sprint' => 'sometimes|integer|nullable',
        ]);

        $jiraPayload = [
            'fields' => []
        ];

        if (isset($data['summary'])) {
            $jiraPayload['fields']['summary'] = $data['summary'];
        }

        if (array_key_exists('description', $data)) {
            $jiraPayload['fields']['description'] = [
                'type' => 'doc',
                'version' => 1,
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => $data['description'] ?? ''],
                        ],
                    ],
                ],
            ];
        }

        if (isset($data['labels'])) {
            $jiraPayload['fields']['labels'] = $data['labels'];
        }

        if (isset($data['assignee'])) {
            $jiraPayload['fields']['assignee'] = isset($data['assignee']['accountId'])
                ? ['accountId' => $data['assignee']['accountId']]
                : null;
        }

        if (isset($data['priority'])) {
            $jiraPayload['fields']['priority'] = $data['priority'] ? ['id' => $data['priority']] : null;
        }

        if (isset($data['status'])) {
            // Usually status update requires a transition, so here you might want to handle transitions separately.
            // For simplicity, skipping status update in this example.
        }

        if (array_key_exists('sprint', $data)) {
            $jiraPayload['fields']['customfield_10020'] = $data['sprint'] ?? null;
        }

        // Clean null fields
        $jiraPayload['fields'] = array_filter($jiraPayload['fields'], fn($v) => $v !== null);

        $response = $this->jiraService->updateTask($taskKey, $jiraPayload);

        if (!$response) {
            return response()->json(['error' => 'Failed to update Jira task'], 500);
        }

        return response()->json($response);
    }

    public function getBoards()
    {
        Log::info("Fetching Jira boards");
        $boards = $this->jiraService->getBoards();

        if (!$boards) {
            return response()->json(['error' => 'Failed to fetch boards'], 500);
        }

        return response()->json($boards);
    }
    public function getSprints(Request $request)
    {
        $request->validate([
            'board_id' => 'required|integer'
        ]);

        $boardId = $request->input('board_id');

        Log::info("Fetching sprints for board: {$boardId}");

        $sprints = $this->jiraService->getSprints($boardId);

        if (!$sprints) {
            return response()->json(['error' => 'Failed to fetch sprints'], 500);
        }

        return response()->json($sprints);
    }
}
