<?php
declare(strict_types=1);

namespace hiqdev\ComposerCiDeps\Service;

/**
 * Class ComposerDownloader is a wrapper for the original ComposerDownloader class,
 * as we need to change the order of downloaders by disabling the original one.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class ComposerDownloader extends \cweagans\Composer\Downloader\ComposerDownloader
{
}
