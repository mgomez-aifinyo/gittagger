<?php

namespace App\lib;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;

class ExtGit extends Git
{
    /** @throws GitException */
    public function open($directory)
    {
        return new ExtGitRepository($directory, $this->runner);
    }
}
