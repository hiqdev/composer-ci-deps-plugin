<?php

namespace hiqdev\ComposerCiDeps;


use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{

    public function activate(Composer $composer, IOInterface $io)
    {
        $requires = $composer->getPackage()->getRequires();

        $a = 1;
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
//           PackageEvents::PRE_PACKAGE_INSTALL => ['installDownloads', 10],
        ];
    }
}
