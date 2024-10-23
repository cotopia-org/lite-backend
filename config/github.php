<?php

return [
    'token'              => env('GITHUB_API_TOKEN', null),
    'issue_repositories' => [
        'cotopia-org/lite-backend',
    ],
    'version'            => env('GITHUB_API_VERSION', '2022-11-28'),
];
