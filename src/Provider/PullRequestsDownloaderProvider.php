<?php
declare(strict_types=1);

namespace hiqdev\ComposerCiDeps\Provider;


use cweagans\Composer\Capability\Downloader\BaseDownloaderProvider;
use hiqdev\ComposerCiDeps\Service\ComposerDownloader;
use hiqdev\ComposerCiDeps\Service\GitLabPullRequestDownloader;

class PullRequestsDownloaderProvider extends BaseDownloaderProvider
{
    public function getDownloaders(): array
    {
        return [
            new GitLabPullRequestDownloader($this->composer, $this->io, $this->plugin),
            new ComposerDownloader($this->composer, $this->io, $this->plugin),
        ];
    }
}
