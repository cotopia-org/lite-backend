<?php

namespace App\Listeners;

use App\Events\JobCreated;
use App\Services\Github;

class CreateGithubIssueFromJob
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     * @throws \Exception
     */
    public function handle(JobCreated $event): void
    {
        $job = $event->job;
        $issue_repos = config('github.issue_repositories');
        $githubSrv = $this->githubService();
        foreach ($issue_repos as $repo) {
            $githubSrv->createIssue($repo, [
                'title'       => $job->title,
                'description' => $job->description,
            ]);
        }
    }

    public function githubService(): Github
    {
        return app('github');
    }
}
