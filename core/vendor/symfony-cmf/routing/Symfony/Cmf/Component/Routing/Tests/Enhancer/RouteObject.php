<?php

namespace Symfony\Cmf\Component\Routing\Tests\Enhancer;

use Symfony\Component\Routing\Route;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Empty abstract class to be able to mock an object that both extends Route
 * and implements RouteObjectInterface
 */
abstract class RouteObject extends Route implements RouteObjectInterface
{
    public function getRouteKey()
    {
        return null;
    }
}
