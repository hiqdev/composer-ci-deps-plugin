<?php

namespace hiqdev\ComposerCiDeps\Provider;

use cweagans\Composer\Capability\Resolver\BaseResolverProvider;
use hiqdev\ComposerCiDeps\Service\PullRequestsResolver;

class PullRequestsResolverProvider extends BaseResolverProvider
{
    public function getResolvers(): array
    {
        return [
            new PullRequestsResolver($this->composer, $this->io, $this->plugin),
        ];
    }
}
