<?php

namespace Symfony\Cmf\Component\Routing\Enhancer;

use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * A route enhancer can change the values in the route data arrays
 *
 * This is useful to provide information to the rest of the routing system
 * that can be inferred from other parameters rather than hardcode that
 * information in every route.
 *
 * @author David Buchmann
 */
interface RouteEnhancerInterface
{
    /**
     * Update the defaults based on its own data and the request.
     *
     * @param array $defaults the getRouteDefaults array
     *
     * @return array the modified defaults. Each enhancer MUST return the $defaults but may add or remove values
     */
    public function enhance(array $defaults, Request $request);
}
