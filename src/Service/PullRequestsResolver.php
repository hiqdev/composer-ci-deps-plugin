<?php
declare(strict_types=1);

namespace hiqdev\ComposerCiDeps\Service;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Resolver\ResolverInterface;
use Throwable;

/**
 * Class PullRequestsResolver processes the replacements file with Pull Request URLs,
 * trying to locate patches from them or marking URLs for different downloaders.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class PullRequestsResolver implements ResolverInterface
{
    private PatchFromUrlFactory $patchLocator;
    private Composer $composer;
    private IOInterface $io;

    public function __construct(Composer $composer, IOInterface $io, PluginInterface $plugin)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->patchLocator = new PatchFromUrlFactory($composer);
    }

    private function locateReplacementsFile(): string
    {
        $vendor = $this->composer->getConfig()->get('vendor-dir');
        $path = realpath("$vendor/../pull-requests.txt");

        return $path;
    }

    public function resolve(PatchCollection $collection): void
    {
        $path = $this->locateReplacementsFile();
        if (!file_exists($path)) {
            return;
        }

        $this->loadReplacements($path, $collection);
    }

    private function loadReplacements(string $path, PatchCollection $collection): void
    {
        if (!file_exists($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            try {
                if (filter_var($line, FILTER_VALIDATE_URL) === false) {
                    continue;
                }

                $patch = $this->patchLocator->locate($line);
                $collection->addPatch($patch);
            } catch (Throwable $e) {
                $this->io->writeError("Error while locating patch from URL: {$line}");
            }
        }
    }
}
