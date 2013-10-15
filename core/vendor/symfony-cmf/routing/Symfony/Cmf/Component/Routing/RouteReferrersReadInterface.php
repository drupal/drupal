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
 * Interface to be implemented by content that wants to be support route generation
 * from content with the DynamicRouter by providing the routes that point to it.
 */
interface RouteReferrersReadInterface
{
    /**
     * Get the routes that point to this content.
     *
     * @return Route[] Route instances that point to this content
     */
    public function getRoutes();
}
