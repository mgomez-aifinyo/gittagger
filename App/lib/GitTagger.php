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

    public function run(string $repoDir = GitTagger::DEFAULT_REPO_PATH, bool $push = false): bool
    {
        $currentTag = self::BASE_SEMVER;

        if (!$this->isValidGitRepositoryDir($repoDir)) {
            fwrite(STDERR, "Error: Directory $repoDir is not a git repository" . PHP_EOL);
            exit(1);
        }

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
                    fwrite(STDERR,"Tag cannot be empty: $nextTag" . PHP_EOL);
                    return false;
                }
                break;
            default:
                fwrite(STDERR, "Invalid option number: $answer" . PHP_EOL);
                return false;
        }

        if ($this->tagExists($nextTag)) {
            fwrite(STDERR,'Tag already exists: %s' . PHP_EOL, $nextTag);
            return false;
        }

        try {
            printf('Generated tag is: %s' . PHP_EOL, $nextTag);
            if (!$push) {
                printf('Dry run mode, skipping tag creation and push' . PHP_EOL);
                return true;
            }
            $this->createTag($nextTag, $nextTag);
            printf('Tag %s created' . PHP_EOL, $nextTag);
            $this->pushTag($nextTag);
            printf('Tag %s pushed to remote %s' . PHP_EOL, $nextTag, self::DEFAULT_REMOTE);
        } catch (GitException $e) {
            fwrite(STDERR, "Failed to create or push tag: {$e->getMessage()}" . PHP_EOL);
            return false;
        }

        return true;
    }

    private function openRepository(string $path = self::DEFAULT_REPO_PATH): void
    {
        try {
            $this->repo = $this->git->open($path);
        } catch (GitException $e) {
            throw new RuntimeException('Failed to open repository: ' . $e->getMessage(), 0, $e);
        }
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
            fwrite(STDERR,'No tags found' . PHP_EOL);
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

            return $this->getPrefix($currentTag) . (string)$version;
        } catch (InvalidVersionException $e) {
            throw new RuntimeException('Failed to parse current tag: ' . $e->getMessage(), 0, $e);
        }
    }

    private function showMenu($currentTag): void
    {
        printf('## GitTagger - Automatic semver based git tag creator ##' . PHP_EOL);
        printf(PHP_EOL);
        printf('Repository: %s' . PHP_EOL, $this->repo->getRepositoryPath());
        printf('Branch: %s' . PHP_EOL, $this->getCurrentRepoBranch());
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

    private function getPrefix(string $currentTag): ?string
    {
        $pattern = '/([a-zA-Z]+)(\d+)/';
        preg_match($pattern, $currentTag, $matches);
        return $matches[1] ?? null;
    }

    private function getCurrentRepoBranch(): string
    {
        try {
            return $this->repo->getCurrentBranchName();
        } catch (GitException $e) {
            throw new RuntimeException('Failed to get current git branch: ' . $e->getMessage(), 0, $e);
        }
    }

    private function isValidGitRepositoryDir(string $repoDir): bool
    {
        if (!is_dir($repoDir) || !is_readable($repoDir) || !is_dir("$repoDir/.git") || !is_readable("$repoDir/.git")) {
            return false;
        }
        return true;
    }
}
