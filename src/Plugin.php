<?php
declare(strict_types=1);

namespace hiqdev\ComposerCiDeps;


use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\EventDispatcher\ScriptExecutionException;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Util\Platform;
use cweagans\Composer\Capability\Downloader\DownloaderProvider;
use cweagans\Composer\Capability\Resolver\ResolverProvider;
use cweagans\Composer\Event\PatchEvent;
use cweagans\Composer\Event\PatchEvents;
use hiqdev\ComposerCiDeps\Provider\PullRequestsDownloaderProvider;
use hiqdev\ComposerCiDeps\Provider\PullRequestsResolverProvider;

class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{
    public function getCapabilities(): array
    {
        return [
            ResolverProvider::class => PullRequestsResolverProvider::class,
            DownloaderProvider::class => PullRequestsDownloaderProvider::class,
        ];
    }

    public function activate(Composer $composer, IOInterface $io)
    {
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            PatchEvents::POST_PATCH_APPLY_ERROR => 'onPostPatchApplyError',
        ];
    }

    public function onPostPatchApplyError(PatchEvent $event)
    {
        if (Platform::getEnv('EXIT_ON_PATCHING_ERROR') == 1) {
            exit(1);
        }
    }
}
