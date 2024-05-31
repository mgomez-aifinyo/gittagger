<?php
namespace App\lib;

use CzProject\GitPhp\GitException;
use Exception;
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
    const BASE_SEMVER = '0.0.0';
    const DEFAULT_REMOTE = 'origin';

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
        $currentTag = self::BASE_SEMVER;
        $this->openRepository($repoDir);

        if ($this->fetchTags()) {
            $currentTag = $this->tagList[count($this->tagList) - 1];
        }

        $this->showMenu($currentTag);


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
                printf('Enter custom tag: ');
                $nextTag = str_replace(' ', '', CliPrompt::prompt());

                if (empty($nextTag))
                {
                    printf('Tag cannot be empty: %s ' . PHP_EOL, $nextTag);
                    return false;
                }
                break;
            default:
                printf('Invalid option number: %s ' . PHP_EOL, $answer);
                return false;
        }

        if ($this->tagExists($nextTag)) {
            printf ('Tag already exists: %s' . PHP_EOL, $nextTag);
            return false;
        }

        try {
            $this->createTag($nextTag, $nextTag);
            printf('Tag %s created' . PHP_EOL, $nextTag);
            $this->pushTag($nextTag);
            printf('Tag %s pushed to remote %s' . PHP_EOL, $nextTag, self::DEFAULT_REMOTE);
        } catch (GitException $e) {
            printf('Failed to create or push tag: %s' . PHP_EOL, $e->getMessage());
            return false;
        }

        return true;
    }

    private function openRepository(string $path = self::DEFAULT_REPO_PATH): void
    {
        $this->repo = $this->git->open($path);
    }

    /**
     * @throws GitException
     */
    private function createTag(string $tagName, string $message): void
    {
        $options = [];
        if ($message !== '') {
            $options['--message'] = "'$message'";
        }
        $this->repo->createTag($tagName, $options);
    }

    /**
     * @throws GitException
     */
    private function pushTag(string $tagName): void
    {
        $this->repo->lpush(self::DEFAULT_REMOTE, ['tag', $tagName] );
    }

    private function fetchTags(): bool
    {
        $this->tagList = $this->repo->getSortedTagsByDate();
        if (empty($this->tagList)) {
            printf('No tags found' . PHP_EOL);
            return false;
        }

        return true;
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

    private function showMenu($currentTag): void
    {
        printf('## GitTagger - Automatic semver based git tag creator ##' . PHP_EOL);
        printf(PHP_EOL);
        printf('Repository: %s' . PHP_EOL, $this->repo->getRepositoryPath());
        printf('Branch: %s' . PHP_EOL, $this->repo->getCurrentBranchName());
        printf('Last created tag: %s' . PHP_EOL, $currentTag);
        printf(PHP_EOL);
        printf('Choose next tag by option number:' . PHP_EOL);
        printf('1. Increase major (%s)' . PHP_EOL, $this->calculateNextTag($currentTag, self::FACTOR_MAJOR));
        printf('2. Increase minor (%s)' . PHP_EOL, $this->calculateNextTag($currentTag, self::FACTOR_MINOR));
        printf('3. Increase revision (%s)' . PHP_EOL, $this->calculateNextTag($currentTag, self::FACTOR_REVISION));
        printf('4. Other/custom' . PHP_EOL);
        printf(PHP_EOL);
        printf('Enter option number: ');
    }
}
