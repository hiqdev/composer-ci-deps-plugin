<?php

namespace hiqdev\ComposerCiDeps\Exception;

use RuntimeException;

class UnresolvedUrlException extends RuntimeException
{
    public static function fromUrlAndPackageName(string $pullRequestUrl, string $packageName)
    {
        return new self("The URL '$pullRequestUrl' resolved to a package '$packageName' that is not found in dependencies.");
    }
}
