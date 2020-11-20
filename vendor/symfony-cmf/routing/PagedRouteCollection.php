<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing;

/**
 * Provides a route collection which avoids having all routes in memory.
 *
 * Internally, this does load multiple routes over time using a
 * PagedRouteProviderInterface $route_provider.
 */
class PagedRouteCollection implements \Iterator, \Countable
{
    /**
     * @var PagedRouteProviderInterface
     */
    protected $provider;

    /**
     * Stores the amount of routes which are loaded in parallel and kept in
     * memory.
     *
     * @var int
     */
    protected $routesBatchSize;

    /**
     * Contains the current item the iterator points to.
     *
     * @var int
     */
    protected $current = -1;

    /**
     * Stores the current loaded routes.
     *
     * @var \Symfony\Component\Routing\Route[]
     */
    protected $currentRoutes;

    public function __construct(PagedRouteProviderInterface $pagedRouteProvider, $routesBatchSize = 50)
    {
        $this->provider = $pagedRouteProvider;
        $this->routesBatchSize = $routesBatchSize;
    }

    /**
     * Loads the next routes into the elements array.
     *
     * @param int $offset The offset used in the db query.
     */
    protected function loadNextElements($offset)
    {
        // If the last batch was smaller than the batch size, this means there
        // are no more routes available.
        if (isset($this->currentRoutes) && count($this->currentRoutes) < $this->routesBatchSize) {
            $this->currentRoutes = array();
        } else {
            $this->currentRoutes = $this->provider->getRoutesPaged($offset, $this->routesBatchSize);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->currentRoutes);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $result = next($this->currentRoutes);
        if (false === $result) {
            $this->loadNextElements($this->current + 1);
        }
        ++$this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->currentRoutes);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return key($this->currentRoutes);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->current = 0;
        $this->currentRoutes = null;
        $this->loadNextElements($this->current);
    }

    /**
     * Gets the number of Routes in this collection.
     *
     * @return int The number of routes
     */
    public function count()
    {
        return $this->provider->getRoutesCount();
    }
}
