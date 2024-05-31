<?php

require dirname(__DIR__) . '/vendor/autoload.php'; // It must be called first

use App\lib\ExtGit;
use App\lib\GitTagger;

$repoDir = null;
if ($argc > 1) {
    $repoDir = $argv[1];
}

(new GitTagger(new ExtGit))->run($repoDir);
