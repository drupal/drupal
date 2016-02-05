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

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ChainRouteCollection extends RouteCollection
{
    /**
     * @var RouteCollection[]
     */
    private $routeCollections = array();

    /**
     * @var RouteCollection
     */
    private $routeCollection;

    public function __clone()
    {
        foreach ($this->routeCollections as $routeCollection) {
            $this->routeCollections[] = clone $routeCollection;
        }
    }

    /**
     * Gets the current RouteCollection as an Iterator that includes all routes.
     *
     * It implements \IteratorAggregate.
     *
     * @see all()
     *
     * @return \ArrayIterator An \ArrayIterator object for iterating over routes
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
        $count = 0;
        foreach ($this->routeCollections as $routeCollection) {
            $count+= $routeCollection->count();
        }

        return $count;
    }

    /**
     * Adds a route.
     *
     * @param string $name  The route name
     * @param Route  $route A Route instance
     */
    public function add($name, Route $route)
    {
        $this->createInternalCollection();
        $this->routeCollection->add($name, $route);
    }

    /**
     * Returns all routes in this collection.
     *
     * @return Route[] An array of routes
     */
    public function all()
    {
        $routeCollectionAll = new RouteCollection();
        foreach ($this->routeCollections as $routeCollection) {
            $routeCollectionAll->addCollection($routeCollection);
        }

        return $routeCollectionAll->all();
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
        foreach ($this->routeCollections as $routeCollection) {
            $route = $routeCollection->get($name);
            if (null !== $route) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Removes a route or an array of routes by name from the collection
     *
     * @param string|array $name The route name or an array of route names
     */
    public function remove($name)
    {
        foreach ($this->routeCollections as $routeCollection) {
            $route = $routeCollection->get($name);
            if (null !== $route) {
                $routeCollection->remove($name);
            }
        }
    }

    /**
     * Adds a route collection at the end of the current set by appending all
     * routes of the added collection.
     *
     * @param RouteCollection $collection A RouteCollection instance
     */
    public function addCollection(RouteCollection $collection)
    {
        $this->routeCollections[] = $collection;
    }

    /**
     * Adds a prefix to the path of all child routes.
     *
     * @param string $prefix       An optional prefix to add before each pattern of the route collection
     * @param array  $defaults     An array of default values
     * @param array  $requirements An array of requirements
     */
    public function addPrefix($prefix, array $defaults = array(), array $requirements = array())
    {
        $this->createInternalCollection();
        foreach ($this->routeCollections as $routeCollection) {
            $routeCollection->addPrefix($prefix, $defaults, $requirements);
        }
    }

    /**
     * Sets the host pattern on all routes.
     *
     * @param string $pattern      The pattern
     * @param array  $defaults     An array of default values
     * @param array  $requirements An array of requirements
     */
    public function setHost($pattern, array $defaults = array(), array $requirements = array())
    {
        $this->createInternalCollection();
        foreach ($this->routeCollections as $routeCollection) {
            $routeCollection->setHost($pattern, $defaults, $requirements);
        }
    }

    /**
     * Adds defaults to all routes.
     *
     * An existing default value under the same name in a route will be overridden.
     *
     * @param array $defaults An array of default values
     */
    public function addDefaults(array $defaults)
    {
        $this->createInternalCollection();
        foreach ($this->routeCollections as $routeCollection) {
            $routeCollection->addDefaults($defaults);
        }
    }

    /**
     * Adds requirements to all routes.
     *
     * An existing requirement under the same name in a route will be overridden.
     *
     * @param array $requirements An array of requirements
     */
    public function addRequirements(array $requirements)
    {
        $this->createInternalCollection();
        foreach ($this->routeCollections as $routeCollection) {
            $routeCollection->addRequirements($requirements);
        }
    }

    /**
     * Adds options to all routes.
     *
     * An existing option value under the same name in a route will be overridden.
     *
     * @param array $options An array of options
     */
    public function addOptions(array $options)
    {
        $this->createInternalCollection();
        foreach ($this->routeCollections as $routeCollection) {
            $routeCollection->addOptions($options);
        }
    }

    /**
     * Sets the schemes (e.g. 'https') all child routes are restricted to.
     *
     * @param string|array $schemes The scheme or an array of schemes
     */
    public function setSchemes($schemes)
    {
        $this->createInternalCollection();
        foreach ($this->routeCollections as $routeCollection) {
            $routeCollection->setSchemes($schemes);
        }
    }

    /**
     * Sets the HTTP methods (e.g. 'POST') all child routes are restricted to.
     *
     * @param string|array $methods The method or an array of methods
     */
    public function setMethods($methods)
    {
        $this->createInternalCollection();
        foreach ($this->routeCollections as $routeCollection) {
            $routeCollection->setMethods($methods);
        }
    }

    /**
     * Returns an array of resources loaded to build this collection.
     *
     * @return ResourceInterface[] An array of resources
     */
    public function getResources()
    {
        $resources = array();
        foreach ($this->routeCollections as $routeCollection) {
            $resources = array_merge($resources, $routeCollection->getResources());
        }

        return array_unique($resources);
    }

    /**
     * Adds a resource for this collection.
     *
     * @param ResourceInterface $resource A resource instance
     */
    public function addResource(ResourceInterface $resource)
    {
        $this->createInternalCollection();
        $this->routeCollection->addResource($resource);
    }

    private function createInternalCollection()
    {
        if (!$this->routeCollection instanceof RouteCollection) {
            $this->routeCollection = new RouteCollection();
            $this->routeCollections[] = $this->routeCollection;
        }
    }
}
