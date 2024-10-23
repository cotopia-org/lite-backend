<?php

namespace App\Services;

use Illuminate\http\Client\Response;

class Github
{
    public function __construct(
        public string $token,
        public string $version
    )
    {
        //
    }

    public function createIssue($repo, array $params)
    {
        $url = sprintf('https://api.github.com/repos/%s/issues', $repo);

        /** @var Response $response */
        $response = \Http::withHeaders([
                                           'Accept'               => 'application/vnd.github+json',
                                           'Authorization'        => 'Bearer ' . $this->token,
                                           'X-GitHub-Api-Version' => $this->version,
                                       ])->post($url, [
            'title'     => $params['title'],
            'body'      => $params['description'],
            'assignees' => [],
            'milestone' => NULL,
            'labels'    => [],
            'projects'  => [3]
        ]);

        if (!$response->successful()) {

            throw new \Exception('Github: Could not create issues' . $response->body());
        }
    }
}
