<?php
declare(strict_types=1);
namespace Viserio\Component\Routing\Proxies;

use Viserio\Component\StaticalProxy\StaticalProxy;

class Route extends StaticalProxy
{
    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public static function getInstanceIdentifier()
    {
        return 'route';
    }
}
