<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Component\Routing;

use Symfony\Component\Routing\Route;

/**
 * Interface to be implemented by content that exposes editable route
 * referrers.
 */
interface RouteReferrersInterface extends RouteReferrersReadInterface
{
    /**
     * Add a route to the collection.
     *
     * @param Route $route
     */
    public function addRoute($route);

    /**
     * Remove a route from the collection.
     *
     * @param Route $route
     */
    public function removeRoute($route);
}
