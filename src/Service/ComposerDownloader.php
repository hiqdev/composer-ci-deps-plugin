<?php
declare(strict_types=1);

namespace hiqdev\ComposerCiDeps\Service;

use cweagans\Composer\Downloader\ComposerDownloader as BaseComposerDownloader;
use cweagans\Composer\Patch;

/**
 * Replaces the original ComposerDownloader to control downloader ordering —
 * the original must be disabled so GitLabPullRequestDownloader runs first.
 * Also adds support for patch paths local to the project root.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class ComposerDownloader extends BaseComposerDownloader
{
    public function download(Patch $patch): void
    {
        if ($this->isAlreadyDownloaded($patch)) {
            return;
        }

        $sourcePath = $this->findLocalFile($patch->url);
        if ($sourcePath !== null) {
            $patch->localPath = $this->copyToTempFile($sourcePath);
            $patch->sha256 = hash_file('sha256', $patch->localPath);
            return;
        }

        parent::download($patch);
    }

    private function isAlreadyDownloaded(Patch $patch): bool
    {
        return !empty($patch->localPath);
    }

    private function findLocalFile(string $url): ?string
    {
        $rootDir = dirname($this->composer->getConfig()->get('vendor-dir'));
        $path = $rootDir . DIRECTORY_SEPARATOR . $url;
        $realPath = realpath($path);

        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            return null;
        }

        return $realPath;
    }

    private function copyToTempFile(string $sourcePath): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'composer-patch-');
        copy($sourcePath, $tempPath);

        return $tempPath;
    }
}
