<?php
namespace App\lib;

use CzProject\GitPhp\GitException;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHLAK\SemVer\Version;
use RuntimeException;
use Seld\CliPrompt\CliPrompt;

class GitTagger
{
    //Default repo path is . (current directory), to be executed in the root of the repository
    const DEFAULT_REPO_PATH = '.';
    const FACTOR_MAJOR = 'major';
    const FACTOR_MINOR = 'minor';
    const FACTOR_REVISION = 'revision';

    /** @var ExtGit */
    private $git;

    /** @var ExtGitRepository */
    private $repo;

    /*** @var string[]|null */
    private $tagList;

    public function __construct(?ExtGit $git)
    {
        $this->git = $git ?? new ExtGit();
    }

    public function run(string $repoDir = GitTagger::DEFAULT_REPO_PATH): bool
    {
        $this->openRepository($repoDir);
        $this->fetchTags();

        $currentTag = $this->tagList[count($this->tagList) - 1];

        echo 'Current tag is: ' . $currentTag . PHP_EOL;
        echo 'Choose next tag by option number: ' . PHP_EOL;
        printf('1. Major (%s)' . PHP_EOL, $this->calculateNextTag($currentTag, self::FACTOR_MAJOR));
        printf('2. Minor (%s)' . PHP_EOL, $this->calculateNextTag($currentTag, self::FACTOR_MINOR));
        printf('3. Revision (%s)' . PHP_EOL, $this->calculateNextTag($currentTag, self::FACTOR_REVISION));
        printf('4. Other' . PHP_EOL);

        echo 'Enter option number: ';
        $answer = CliPrompt::prompt();
        switch ($answer) {
            case 1:
                $nextTag = $this->calculateNextTag($currentTag, self::FACTOR_MAJOR);
                break;
            case 2:
                $nextTag = $this->calculateNextTag($currentTag, self::FACTOR_MINOR);
                break;
            case 3:
                $nextTag = $this->calculateNextTag($currentTag, self::FACTOR_REVISION);
                break;
            case 4:
                echo 'Enter new tag: ';
                $nextTag = CliPrompt::prompt();
                break;
            default:
                printf('Invalid option number: %s ' . PHP_EOL, $answer);
                return false;
        }

        if ($this->tagExists($nextTag)) {
            echo 'Tag already exists: ' . $nextTag . PHP_EOL;
            return false;
        }

        try {
            $this->createTag($nextTag, $nextTag);
            $this->pushTags();
        } catch (GitException $e) {
            echo 'Failed to create tag: ' . $e->getMessage() . PHP_EOL;
            return false;
        }

        return true;
    }

    private function openRepository(string $path = self::DEFAULT_REPO_PATH): void
    {
        $this->repo = $this->git->open($path);
    }

    /**
     * @param string $tagName
     * @param string $message
     * @return void
     * @throws GitException
     */
    private function createTag(string $tagName, string $message): void
    {
        $options = [];
        if ($message !== '') {
            $options['message'] = $message;
        }
        $this->repo->createTag($tagName, $options);
    }

    /**
     * @return void
     * @throws GitException
     */
    private function pushTags(): void
    {
        $this->repo->push(null, ['--tags'] );
    }

    private function fetchTags(): void
    {
        try {
            $this->tagList = $this->repo->getSortedTagsByDate();
        } catch (GitException $e) {
            throw new RuntimeException('Failed to fetch tags: ' . $e->getMessage(), 0, $e);
        }
    }

    private function tagExists(string $tagName): bool
    {
        return in_array($tagName, $this->tagList);
    }

    private function calculateNextTag(string $currentTag, ?string $factorToIncrement = self::FACTOR_REVISION): ?string
    {
        try {
            $version = Version::parse($currentTag);
            switch ($factorToIncrement)
            {
                case self::FACTOR_MAJOR:
                    $version->incrementMajor();
                    break;
                case self::FACTOR_MINOR:
                    $version->incrementMinor();
                    break;
                case self::FACTOR_REVISION:
                    $version->incrementPatch();
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid factor to increment: ' . $factorToIncrement);
            }

            return (string)$version;
        } catch (InvalidVersionException $e) {
            throw new RuntimeException('Failed to parse current tag: ' . $e->getMessage(), 0, $e);
        }
    }
}
