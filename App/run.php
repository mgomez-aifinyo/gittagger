<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\lib\ExtGit;
use App\lib\GitTagger;

$repoDir = null;
if ($argc > 1) {
    $repoDir = $argv[1];
}

(new GitTagger(new ExtGit))->run($repoDir);
