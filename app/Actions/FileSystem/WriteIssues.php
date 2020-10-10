<?php

namespace App\Actions\FileSystem;

use App\Actions\BaseAction;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\HtmlString;
use Parsedown;
use stdClass;

/**
 * Class WriteIssues
 *
 * @package App\Actions\FileSystem
 */
class WriteIssues extends BaseAction
{
    /**
     * Method for writing all the issues into simple HTML files.
     *
     * @param  string $response     The api response body that contains all the issues on the repository.
     * @param  string $repository   The GitHub repository name. format: user/repository
     * @return array
     *
     * @throws \App\Exceptions\StubNotFoundException
     */
    public function execute(string $response, string $repository): array
    {
        $this->createOrCleanDirectory($repository);

        foreach ($this->workableArray($response) as $issue) {
            $filename = $this->composeFilename($issue);
            $this->getFileSystem()->put($this->getPath($repository, $filename), $this->populateStub($issue));
        }

        return ['filePath' => $this->getDirectory($repository)];
    }

    /**
     * @param  string $response
     * @return array
     */
    private function workableArray(string $response): array
    {
        return json_decode($response);
    }

    private function getPath(string $repository, string $filename)
    {
        return $this->getDirectory($repository) . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @return string|string[]
     *
     * @throws \App\Exceptions\StubNotFoundException
     */
    private function populateStub($issue)
    {
        $stub = $this->getStub();

        foreach ($this->getPopulateData($issue) as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }

    private function getPopulateData($issue): array
    {
        return [
            ':pageTitle' => "Issue #{$issue->id} | IssueCLI",
            ':title' => $issue->title,
            ':creator' => $issue->user->login,
            ':createdAt' => Carbon::parse($issue->created_at)->format('d/m/Y'),
            ':status' => ucfirst($issue->state),
            ':issueNumber' => '#' . $issue->number,
            ':issueTitle' => '| ' .$issue->title,
            ':assignees' => $this->getAssignees($issue),
            ':labels' => $this->getLabels($issue),
            ':issueContext' => $this->renderMarkdown($issue->body),
            ':repository' => $issue->html_url,
        ];
    }

    private function composeFilename($issue)
    {
        $filename = "{$issue->number}-{$issue->title}.html";;
        $filename = strtolower($filename);

        return str_replace(' ', '-', $filename);
    }

    private function getAssignees($issue)
    {
        if (count($issue->assignees) > 0) {
            return collect($issue->assignees)->pluck('login')->implode(', ');
        }

        return 'none';
    }

    /**
     * Method for getting and formatting all the issue labels in the application.
     *
     * @param  stdClass $issue The issue content from the API response.
     * @return string
     */
    private function getLabels(stdClass $issue): string
    {
        if (count($issue->labels) > 0) {
            return collect($issue->labels)->pluck('name')->implode(', ');
        }

        return 'none';
    }

    /**
     * Method for converting the issue body markdown to HTML.
     *
     * @param  string $text The issue body that we will get from the API response.
     * @return HtmlString|string|string[]
     */
    public function renderMarkdown(string $text)
    {
        $content = new HtmlString(app(Parsedown::class)->setSafeMode(true)->text($text));

        return str_replace('<p>', '<p class="card-text">', $content);
    }
}
