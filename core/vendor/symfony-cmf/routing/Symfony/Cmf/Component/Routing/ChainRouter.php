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

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Psr\Log\LoggerInterface;

/**
 * ChainRouter
 *
 * Allows access to a lot of different routers.
 *
 * @author Henrik Bjornskov <henrik@bjrnskov.dk>
 * @author Magnus Nordlander <magnus@e-butik.se>
 */
class ChainRouter implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    /**
     * @var \Symfony\Component\Routing\RequestContext
     */
    private $context;

    /**
     * Array of arrays of routers grouped by priority
     * @var array
     */
    private $routers = array();

    /**
     * @var \Symfony\Component\Routing\RouterInterface[] Array of routers, sorted by priority
     */
    private $sortedRouters;

    /**
     * @var \Symfony\Component\Routing\RouteCollection
     */
    private $routeCollection;

    /**
     * @var null|\Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @return RequestContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Add a Router to the index
     *
     * @param RouterInterface $router   The router instance
     * @param integer         $priority The priority
     */
    public function add(RouterInterface $router, $priority = 0)
    {
        if (empty($this->routers[$priority])) {
            $this->routers[$priority] = array();
        }

        $this->routers[$priority][] = $router;
        $this->sortedRouters = array();
    }

    /**
     * Sorts the routers and flattens them.
     *
     * @return RouterInterface[]
     */
    public function all()
    {
        if (empty($this->sortedRouters)) {
            $this->sortedRouters = $this->sortRouters();

            // setContext() is done here instead of in add() to avoid fatal errors when clearing and warming up caches
            // See https://github.com/symfony-cmf/Routing/pull/18
            $context = $this->getContext();
            if (null !== $context) {
                foreach ($this->sortedRouters as $router) {
                    if ($router instanceof RequestContextAwareInterface) {
                        $router->setContext($context);
                    }
                }
            }
        }

        return $this->sortedRouters;
    }

    /**
     * Sort routers by priority.
     * The highest priority number is the highest priority (reverse sorting)
     *
     * @return RouterInterface[]
     */
    protected function sortRouters()
    {
        $sortedRouters = array();
        krsort($this->routers);

        foreach ($this->routers as $routers) {
            $sortedRouters = array_merge($sortedRouters, $routers);
        }

        return $sortedRouters;
    }

    /**
     * {@inheritdoc}
     *
     * Loops through all routes and tries to match the passed url.
     *
     * Note: You should use matchRequest if you can.
     */
    public function match($url)
    {
        return $this->doMatch($url);
    }

    /**
     * {@inheritdoc}
     *
     * Loops through all routes and tries to match the passed request.
     */
    public function matchRequest(Request $request)
    {
        return $this->doMatch($request->getPathInfo(), $request);
    }

    /**
     * Loops through all routers and tries to match the passed request or url.
     *
     * At least the  url must be provided, if a request is additionally provided
     * the request takes precedence.
     *
     * @param string  $url
     * @param Request $request
     */
    private function doMatch($url, Request $request = null)
    {
        $methodNotAllowed = null;

        foreach ($this->all() as $router) {
            try {
                // the request/url match logic is the same as in Symfony/Component/HttpKernel/EventListener/RouterListener.php
                // matching requests is more powerful than matching URLs only, so try that first
                if ($router instanceof RequestMatcherInterface) {
                    if (null === $request) {
                        $request = Request::create($url);
                    }

                    return $router->matchRequest($request);
                }
                // every router implements the match method
                return $router->match($url);
            } catch (ResourceNotFoundException $e) {
                if ($this->logger) {
                    $this->logger->info('Router '.get_class($router).' was not able to match, message "'.$e->getMessage().'"');
                }
                // Needs special care
            } catch (MethodNotAllowedException $e) {
                if ($this->logger) {
                    $this->logger->info('Router '.get_class($router).' throws MethodNotAllowedException with message "'.$e->getMessage().'"');
                }
                $methodNotAllowed = $e;
            }
        }

        $info = $request
            ? "this request\n$request"
            : "url '$url'";
        throw $methodNotAllowed ?: new ResourceNotFoundException("None of the routers in the chain matched $info");
    }

    /**
     * {@inheritdoc}
     *
     * Loops through all registered routers and returns a router if one is found.
     * It will always return the first route generated.
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        $debug = array();

        foreach ($this->all() as $router) {
            // if $router does not implement ChainedRouterInterface and $name is not a string, continue
            if ($name && !$router instanceof ChainedRouterInterface) {
                if (! is_string($name)) {
                    continue;
                }
            }

            // If $router implements ChainedRouterInterface but doesn't support this route name, continue
            if ($router instanceof ChainedRouterInterface && !$router->supports($name)) {
                continue;
            }

            try {
                return $router->generate($name, $parameters, $absolute);
            } catch (RouteNotFoundException $e) {
                $hint = $this->getErrorMessage($name, $router, $parameters);
                $debug[] = $hint;
                if ($this->logger) {
                    $this->logger->info('Router '.get_class($router)." was unable to generate route. Reason: '$hint': ".$e->getMessage());
                }
            }
        }

        if ($debug) {
            $debug = array_unique($debug);
            $info = implode(', ', $debug);
        } else {
            $info = $this->getErrorMessage($name);
        }

        throw new RouteNotFoundException(sprintf('None of the chained routers were able to generate route: %s', $info));
    }

    private function getErrorMessage($name, $router = null, $parameters = null)
    {
        if ($router instanceof VersatileGeneratorInterface) {
            $displayName = $router->getRouteDebugMessage($name, $parameters);
        } elseif (is_object($name)) {
            $displayName = method_exists($name, '__toString')
                ? (string) $name
                : get_class($name)
            ;
        } else {
            $displayName = (string) $name;
        }

        return "Route '$displayName' not found";
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        foreach ($this->all() as $router) {
            if ($router instanceof RequestContextAwareInterface) {
                $router->setContext($context);
            }
        }

        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * check for each contained router if it can warmup
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->all() as $router) {
            if ($router instanceof WarmableInterface) {
                $router->warmUp($cacheDir);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        if (!$this->routeCollection instanceof RouteCollection) {
            $this->routeCollection = new RouteCollection();
            foreach ($this->all() as $router) {
                $this->routeCollection->addCollection($router->getRouteCollection());
            }
        }

        return $this->routeCollection;
    }
}
