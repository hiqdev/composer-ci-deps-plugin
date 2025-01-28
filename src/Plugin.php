<?php

namespace hiqdev\ComposerCiDeps;


use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\Downloader\DownloaderProvider;
use cweagans\Composer\Capability\Resolver\ResolverProvider;
use cweagans\Composer\Downloader\ComposerDownloader;
use cweagans\Composer\Resolver\Dependencies;
use cweagans\Composer\Resolver\PatchesFile;
use cweagans\Composer\Resolver\RootComposer;
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
        $extra = $composer->getPackage()->getExtra();
        if (isset($extra['composer-patches'])) {
            return;
        }

        $extra['composer-patches'] = [
            'disable-resolvers' => [
                '\\' . RootComposer::class,
                '\\' . PatchesFile::class,
                '\\' . Dependencies::class,
            ],
            'disable-downloaders' => [
                '\\' . ComposerDownloader::class,
            ],
        ];

        $plugins = $composer->getPluginManager()->getPlugins();
        foreach ($plugins as $plugin) {
            if ($plugin instanceof \cweagans\Composer\Plugin\Patches) {
                $plugin->configure($extra, 'composer-patches');
                break;
            }
        }
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public static function getSubscribedEvents()
    {
        return [];
    }
}
