<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\NestedMatcher;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;

/**
 * A RouteFilter takes a RouteCollection and returns a filtered subset.
 *
 * It is not implemented as a filter iterator because we want to allow
 * router filters to handle their own empty-case handling, usually by throwing
 * an appropriate exception if no routes match the object's rules.
 *
 * @author Larry Garfield
 * @author David Buchmann
 */
interface RouteFilterInterface
{
    /**
     * Filters the route collection against a request and returns all matching
     * routes.
     *
     * @param RouteCollection $collection The collection against which to match.
     * @param Request         $request    A Request object against which to match.
     *
     * @return RouteCollection A non-empty RouteCollection of matched routes.
     *
     * @throws ResourceNotFoundException if none of the routes in $collection
     *                                   matches $request. This is a performance
     *                                   optimization to not continue the match
     *                                   process when a match will no longer be
     *                                   possible.
     */
    public function filter(RouteCollection $collection, Request $request);
}
