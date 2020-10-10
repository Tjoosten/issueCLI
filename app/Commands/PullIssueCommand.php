<?php

namespace App\Commands;

use App\Actions\FileSystem\WriteIssues;
use App\Services\GithubService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Response;
use LaravelZero\Framework\Commands\Command;

/**
 * Class PullIssueCommand
 *
 * @package App\Commands
 */
class PullIssueCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'issues:pull
                            {name : The name of the Github repository (required)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Pull all the issues for the given GitHub repository.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $repositoryName = $this->argument('name');
        $githubService = new GithubService;
        $request = $githubService->getIssues($repositoryName);

        switch ($request->status()) {
            case Response::HTTP_NOT_FOUND: $this->httpNotFoundResponse($repositoryName);                   break;
            case Response::HTTP_OK:        $this->storeIssuesLocally($repositoryName, $request->body());   break;
        }
    }

    private function httpNotFoundResponse(string $repositoryName)
    {
        $this->comment("Could Not found the given repository. (given repository name: {$repositoryName})");
    }

    private function storeIssuesLocally(string $repositoryName, string $responseBody)
    {
        $storageAction = (new WriteIssues)->execute($responseBody, $repositoryName);

        $this->info("All the issues for {$repositoryName} are generated to html files and stored in {$storageAction['filePath']}");
    }
}
