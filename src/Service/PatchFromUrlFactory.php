<?php

namespace hiqdev\ComposerCiDeps\Service;

use Composer\Composer;
use Composer\Package\BasePackage;
use cweagans\Composer\Patch;
use hiqdev\ComposerCiDeps\Exception\UnresolvedUrlException;

/**
 * Locates patches by pull request URL.
 * 
 * Example PR urls: 
 *  - https://gitlab.com/ultimateretro/ultimateretro/-/merge_requests/49
 *  - https://github.com/hiqdev/php-billing/pull/94
 */
class PatchFromUrlFactory
{
    private Composer $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    public function locate(string $pullRequestUrl): Patch
    {
        $package = $this->getPackageByPullRequestUrl($pullRequestUrl);
        $pullRequestUrl = trim($pullRequestUrl);

        $patch = new Patch();
        $patch->package = $package->getName();
        $patch->description = 'Requested in Pull Request for CI';
        $patch->url = $pullRequestUrl;
        if (!$this->isGitLabLink($pullRequestUrl)) {
            $patch->url = $pullRequestUrl . '.patch';
        }
        $patch->extra = array_filter([
            'gitlab' => $this->isGitLabLink($pullRequestUrl) ? true : null,
        ]);

        return $patch;
    }

    private function isGitLabLink(string $pullRequestUrl): bool
    {
        return str_contains($pullRequestUrl, '-/merge_requests');
    }

    private function getPackageByPullRequestUrl(string $pullRequestUrl): BasePackage
    {
        $path = parse_url($pullRequestUrl, PHP_URL_PATH);
        $path = explode('/', trim($path, '/'));
        $packageName = implode('/', array_slice($path, 0, 2));

        $package = $this->composer->getRepositoryManager()
                       ->getLocalRepository()
                       ->findPackage($packageName, '*');

        if ($package === null) {
            throw UnresolvedUrlException::fromUrlAndPackageName($pullRequestUrl, $packageName);
        }

        return $package;
    }
}
