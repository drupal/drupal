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

/**
 * Document for redirection entries with the RedirectController.
 *
 * Defines additional methods needed by the RedirectController to redirect
 * based on the route.
 *
 * This document may define (in order of precedence - the others can be empty):
 *
 * - uri: an absolute uri
 * - routeName and routeParameters: to be used with the standard symfony router
 *   or a route entry in the routeParameters for the DynamicRouter. Precedency
 *   between these is determined by the order of the routers in the chain
 *   router.
 *
 * With standard Symfony routing, you can just use uri / routeName and a
 * hashmap of parameters.
 *
 * For the dynamic router, you can return a RouteInterface instance in the
 * field 'route' of the parameters.
 *
 * Note: getRedirectContent must return the redirect route itself for the
 * integration with DynamicRouter to work.
 *
 * @author David Buchmann <david@liip.ch>
 */
interface RedirectRouteInterface extends RouteObjectInterface
{
    /**
     * Get the absolute uri to redirect to external domains.
     *
     * If this is non-empty, the other methods won't be used.
     *
     * @return string target absolute uri
     */
    public function getUri();

    /**
     * Get the target route document this route redirects to.
     *
     * If non-null, it is added as route into the parameters, which will lead
     * to have the generate call issued by the RedirectController to have
     * the target route in the parameters.
     *
     * @return RouteObjectInterface the route this redirection points to
     */
    public function getRouteTarget();

    /**
     * Get the name of the target route for working with the symfony standard
     * router.
     *
     * @return string target route name
     */
    public function getRouteName();

    /**
     * Whether this should be a permanent or temporary redirect
     *
     * @return boolean
     */
    public function isPermanent();

    /**
     * Get the parameters for the target route router::generate()
     *
     * Note that for the DynamicRouter, you return the target route
     * document as field 'route' of the hashmap.
     *
     * @return array Information to build the route
     */
    public function getParameters();
}
