# GitTagger

GitTagger is a simple tool to calculate next version number based on git tags sequence and push the next tag.

## Installation and usage

1. Clone the repository
2. Choose one of the usage options below

## Bash shell script (No PHP required)

    cd bash
    ./gittagger.sh <repository path>

## Phar script (PHP >=7.1 required)

    php gittagger.phar <repository path>

## PHP script (PHP >=7.1 required)

```
    composer install
    php App/run.php <repository path>
