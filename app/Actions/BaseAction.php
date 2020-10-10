<?php

namespace App\Actions;

use App\Exceptions\StubNotFoundException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

class BaseAction
{
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
    }

    protected function createOrCleanDirectory(string $repository): void
    {
        $directory = $this->getDirectory($repository);

        if (!$this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        } else {
            $this->filesystem->cleanDirectory($directory);
        }
    }

    /**
     * Method for getting the internal fileSystem in other classes then BaseAction.
     *
     * @return Filesystem
     */
    public function getFileSystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Method for getting the output directory in the system.
     *
     * In this directory all issue reports will be placed by the system.
     *
     * @param  string $repository The GitHUb repository name that is given.
     * @return string
     */
    protected function getDirectory(string $repository): string
    {
        return getcwd() . DIRECTORY_SEPARATOR . $repository . '-issues';
    }

    /**
     * Method for getting the stub in the system.
     *
     * @return string
     *
     * @throws StubNotFoundException
     */
    protected function getStub(): string
    {
        try {
            return $this->filesystem->get(__DIR__ .'/../../resources/stubs/issue-template.stub');
        } catch (FileNotFoundException $e) {
            throw new StubNotFoundException('Cannot find the issue template stub');
        }
    }
}
