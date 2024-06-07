# GitTagger

GitTagger is a simple tool to calculate next version number based on git tags sequence and push the next tag.

## Installation and usage

1. Clone the repository
2. Choose one of the usage options below

## Phar script (PHP >=7.1 required)

    php gittagger.phar [options] <repository path>

## PHP script (PHP >=7.1 required)

    composer install
    php App/run.php [options] <repository path>

### Command Options
 `--help`, `-h` Show help message 

 `--push`, `-p`: Create and push the new tag to the remote repository

## Bash shell script (No PHP required)
(command options not yet implemented)

    cd bash
    ./gittagger.sh <repository path>
