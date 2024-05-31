<?php

namespace App\lib;

use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;

class ExtGitRepository extends GitRepository
{
    public function getSortedTagsByDate(): array
    {
        $command = 'git for-each-ref --sort=taggerdate --format \'%(refname:short) %(taggerdate)\' refs/tags';
        exec('pwd', $output);
        $curDir = $output[0];
        chdir($this->repository);
        $output = null;
        exec($command, $output);
        chdir($curDir);

        foreach ($output as $index => $item) {
            $output[$index] = explode(' ', $item)[0];
        }

        return $output;
    }

    /**
     * Push changes to a remote
     * @param  string|string[]|null $remote
     * @param  array|null $options
     * @return static
     * @throws GitException
     */
    public function lpush(?string $remote = null, array $options = null): self
    {
        $this->run('push', $remote, $options);
        return $this;
    }
}
