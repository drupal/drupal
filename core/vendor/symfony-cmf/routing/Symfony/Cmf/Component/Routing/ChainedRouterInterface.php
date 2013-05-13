<?php

namespace Symfony\Cmf\Component\Routing;

use Symfony\Component\Routing\RouterInterface;

/**
 * Interface to combine the VersatileGeneratorInterface with the RouterInterface
 */
interface ChainedRouterInterface extends RouterInterface, VersatileGeneratorInterface
{
    // nothing new to add
}
