<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\lib\ExtGit;
use App\lib\GitTagger;

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        fwrite(STDERR, "Fatal error: {$error['message']}\n");
    }
});

function getRealPath(string $path): string
{
    // If the path is absolute, return the real path
    if (substr($path, 0, 1) === '/') {
        return realpath($path);
    }

    // Determine the base directory
    $baseDir = Phar::running() !== '' ? dirname(Phar::running(false)) : getcwd();

    // If the path starts with ~, replace ~ with the home directory
    if (substr($path, 0, 1) === '~') {
        $path = $_SERVER['HOME'] . substr($path, 1);
    }

    return realpath($baseDir . '/' . $path);
}

$options = getopt("hp", ["help", "push"]);

// Check if the -h or --help option is set
$help = isset($options['h']) || isset($options['help']);

if ($help || $argc < 2) {
    echo '## GitTagger - Automatic semver based git tag creator ##' . PHP_EOL;
    echo "Usage: $argv[0] [options] <directory>" . PHP_EOL;
    echo PHP_EOL;
    echo "Options:" . PHP_EOL;
    echo "  -h, --help      Show this help message and exit" . PHP_EOL;
    echo "  -p, --push      Creates and pushes the generated tag in the git repository" . PHP_EOL;
    echo PHP_EOL;
    echo "Arguments:" . PHP_EOL;
    echo "  <directory>     The directory to work with. Must be a valid and readable directory." . PHP_EOL;
    exit();
}

// Check if the --push option is set
$push = isset($options['p']) || isset($options['push']);

// Check if the last argument after the script name is a valid directory
$lastArgPosition = count($argv) - 1;
$realpath = getRealPath($argv[$lastArgPosition]);
if (is_dir($realpath) && is_readable($realpath)) {
    (new GitTagger(new ExtGit))->run($realpath, $push);
} else {
    fwrite(STDERR, "Error: The last argument must be a valid and readable directory\n");
    exit(1);
}
