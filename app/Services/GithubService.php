<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * Class GithubService
 *
 * @package App\Services
 */
class GithubService
{
    public function baseHttp()
    {
        return Http::withToken(config('github.github_token'));
    }

    public function getIssues(string $repositoryName, string $method = 'GET')
    {
        return $this->baseHttp()->$method($this->baseUrl() . "/repos/{$repositoryName}/issues", [
            'state' => 'open',
        ]);
    }

    private function baseUrl(): string
    {
        return 'http://api.github.com';
    }
}
