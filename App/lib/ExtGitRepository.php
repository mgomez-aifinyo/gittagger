<?php

namespace App\lib;

use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;

class ExtGitRepository extends GitRepository
{
    public function getSortedTagsByDate()
    {
//         $result = $this->run('for-each-ref', '--sort=taggerdate', "--format '%(refname:short) %(taggerdate)'", 'refs/tags');
        $command = 'git for-each-ref --sort=taggerdate --format \'%(refname:short) %(taggerdate)\' refs/tags';
        exec('pwd', $output);
        $curDir = $output[0];
        chdir($this->repository);
        exec($command, $output);
        chdir($curDir);

        foreach ($output as $index => $item) {
            $output[$index] = explode(' ', $item)[0];
        }


        return $output;
    }
}
