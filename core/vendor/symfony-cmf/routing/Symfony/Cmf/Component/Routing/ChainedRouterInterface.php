<?php

namespace Symfony\Cmf\Component\Routing;

use Symfony\Component\Routing\RouterInterface;

/**
 * Use this interface on custom routers that can handle non-string route
 * "names".
 */
interface ChainedRouterInterface extends RouterInterface
{
    /**
     * Whether the router supports the thing in $name to generate a route.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.

     * @param mixed $name The route name or route object
     *
     * @return bool
     */
    public function supports($name);
}