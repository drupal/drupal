<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class LazyRouteCollection extends RouteCollection
{
    /**
     * The route provider for this generator.
     *
     * @var RouteProviderInterface
     */
    protected $provider;

    public function __construct(RouteProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Gets the number of Routes in this collection.
     *
     * @return int The number of routes
     */
    public function count()
    {
        return count($this->all());
    }

    /**
     * Returns all routes in this collection.
     *
     * @return Route[] An array of routes
     */
    public function all()
    {
        return $this->provider->getRoutesByNames(null);
    }

    /**
     * Gets a route by name.
     *
     * @param string $name The route name
     *
     * @return Route|null A Route instance or null when not found
     */
    public function get($name)
    {
        try {
            return $this->provider->getRouteByName($name);
        } catch (RouteNotFoundException $e) {
            return null;
        }
    }
}
