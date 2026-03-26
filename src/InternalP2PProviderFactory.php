<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalP2P;

use Payroad\Port\Provider\ProviderFactoryInterface;

final class InternalP2PProviderFactory implements ProviderFactoryInterface
{
    public function create(array $config): InternalP2PProvider
    {
        return new InternalP2PProvider();
    }
}
