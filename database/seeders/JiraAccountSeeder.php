<?php

namespace Database\Seeders;

use App\Models\JiraAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class JiraAccountSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        JiraAccount::create([
            'user_id' => $user->id,
            'site_url' => env('JIRA_DEFAULT_SITE', 'https://your-site.atlassian.net'),
            'email' => 'your-email@example.com',
            'api_token' => 'your_api_token_here',
        ]);
    }
}
