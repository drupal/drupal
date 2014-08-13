<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\NestedMatcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;

/**
 * A more flexible approach to matching. The route collection to match against
 * can be dynamically determined based on the request and users can inject
 * their own filters or use a custom final matching strategy.
 *
 * The nested matcher splits matching into three configurable steps:
 *
 * 1) Get potential matches from a RouteProviderInterface
 * 2) Apply any RouteFilterInterface to reduce the route collection
 * 3) Have FinalMatcherInterface select the best match of the remaining routes
 *
 * @author Larry Garfield
 * @author David Buchmann
 */
class NestedMatcher implements RequestMatcherInterface
{
    /**
     * The route provider responsible for the first-pass match.
     *
     * @var RouteProviderInterface
     */
    protected $routeProvider;

    /**
     * The final matcher.
     *
     * @var FinalMatcherInterface
     */
    protected $finalMatcher;

    /**
     * An array of RouteFilterInterface objects.
     *
     * @var RouteFilterInterface[]
     */
    protected $filters = array();

    /**
     * Array of RouteFilterInterface objects, sorted.
     *
     * @var RouteFilterInterface[]
     */
    protected $sortedFilters = array();

    /**
     * Constructs a new NestedMatcher
     *
     * @param RouteProviderInterface $provider The route provider this matcher
     *                                         should use
     * @param FinalMatcherInterface  $final    The Final Matcher to match the
     *                                         routes
     */
    public function __construct(
        RouteProviderInterface $provider = null,
        FinalMatcherInterface $final = null
    ) {
        if (null !== $provider) {
            $this->setRouteProvider($provider);
        }
        if (null !== $final) {
            $this->setFinalMatcher($final);
        }
    }

    /**
     * Sets the route provider for the matching plan.
     *
     * @param RouteProviderInterface $provider A source of routes.
     *
     * @return NestedMatcher this object to have a fluent interface
     */
    public function setRouteProvider(RouteProviderInterface $provider)
    {
        $this->routeProvider = $provider;

        return $this;
    }

    /**
     * Adds a partial matcher to the matching plan.
     *
     * Partial matchers will be run in the order in which they are added.
     *
     * @param RouteFilterInterface $filter
     * @param int                  $priority (optional) The priority of the
     *                                       filter. Higher number filters will
     *                                       be used first. Defaults to 0.
     *
     * @return NestedMatcher this object to have a fluent interface
     */
    public function addRouteFilter(RouteFilterInterface $filter, $priority = 0)
    {
        if (empty($this->filters[$priority])) {
            $this->filters[$priority] = array();
        }

        $this->filters[$priority][] = $filter;
        $this->sortedFilters = array();

        return $this;
    }

    /**
     * Sets the final matcher for the matching plan.
     *
     * @param FinalMatcherInterface $final The final matcher that will have to
     *                                     pick the route that will be used.
     *
     * @return NestedMatcher this object to have a fluent interface
     */
    public function setFinalMatcher(FinalMatcherInterface $final)
    {
        $this->finalMatcher = $final;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        $collection = $this->routeProvider->getRouteCollectionForRequest($request);
        if (!count($collection)) {
            throw new ResourceNotFoundException();
        }

        // Route filters are expected to throw an exception themselves if they
        // end up filtering the list down to 0.
        foreach ($this->getRouteFilters() as $filter) {
            $collection = $filter->filter($collection, $request);
        }

        $attributes = $this->finalMatcher->finalMatch($collection, $request);

        return $attributes;
    }

    /**
     * Sorts the filters and flattens them.
     *
     * @return RouteFilterInterface[] the filters ordered by priority
     */
    public function getRouteFilters()
    {
        if (empty($this->sortedFilters)) {
           $this->sortedFilters = $this->sortFilters();
        }

        return $this->sortedFilters;
    }

    /**
     * Sort filters by priority.
     *
     * The highest priority number is the highest priority (reverse sorting).
     *
     * @return RouteFilterInterface[] the sorted filters
     */
    protected function sortFilters()
    {
        $sortedFilters = array();
        krsort($this->filters);

        foreach ($this->filters as $filters) {
            $sortedFilters = array_merge($sortedFilters, $filters);
        }

        return $sortedFilters;
    }
}
