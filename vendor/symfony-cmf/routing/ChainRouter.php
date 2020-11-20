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

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
 * The ChainRouter allows to combine several routers to try in a defined order.
 *
 * @author Henrik Bjornskov <henrik@bjrnskov.dk>
 * @author Magnus Nordlander <magnus@e-butik.se>
 */
class ChainRouter implements ChainRouterInterface, WarmableInterface
{
    /**
     * @var RequestContext
     */
    private $context;

    /**
     * Array of arrays of routers grouped by priority.
     *
     * @var array
     */
    private $routers = array();

    /**
     * @var RouterInterface[] Array of routers, sorted by priority
     */
    private $sortedRouters;

    /**
     * @var RouteCollection
     */
    private $routeCollection;

    /**
     * @var null|LoggerInterface
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
     * {@inheritdoc}
     */
    public function add($router, $priority = 0)
    {
        if (!$router instanceof RouterInterface
            && !($router instanceof RequestMatcherInterface && $router instanceof UrlGeneratorInterface)
        ) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid router.', get_class($router)));
        }
        if (empty($this->routers[$priority])) {
            $this->routers[$priority] = array();
        }

        $this->routers[$priority][] = $router;
        $this->sortedRouters = array();
    }

    /**
     * {@inheritdoc}
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
     * The highest priority number is the highest priority (reverse sorting).
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
    public function match($pathinfo)
    {
        return $this->doMatch($pathinfo);
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
     * @param string  $pathinfo
     * @param Request $request
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If no router matched.
     */
    private function doMatch($pathinfo, Request $request = null)
    {
        $methodNotAllowed = null;

        $requestForMatching = $request;
        foreach ($this->all() as $router) {
            try {
                // the request/url match logic is the same as in Symfony/Component/HttpKernel/EventListener/RouterListener.php
                // matching requests is more powerful than matching URLs only, so try that first
                if ($router instanceof RequestMatcherInterface) {
                    if (empty($requestForMatching)) {
                        $requestForMatching = $this->rebuildRequest($pathinfo);
                    }

                    return $router->matchRequest($requestForMatching);
                }

                // every router implements the match method
                return $router->match($pathinfo);
            } catch (ResourceNotFoundException $e) {
                if ($this->logger) {
                    $this->logger->debug('Router '.get_class($router).' was not able to match, message "'.$e->getMessage().'"');
                }
                // Needs special care
            } catch (MethodNotAllowedException $e) {
                if ($this->logger) {
                    $this->logger->debug('Router '.get_class($router).' throws MethodNotAllowedException with message "'.$e->getMessage().'"');
                }
                $methodNotAllowed = $e;
            }
        }

        $info = $request
            ? "this request\n$request"
            : "url '$pathinfo'";
        throw $methodNotAllowed ?: new ResourceNotFoundException("None of the routers in the chain matched $info");
    }

    /**
     * {@inheritdoc}
     *
     * Loops through all registered routers and returns a router if one is found.
     * It will always return the first route generated.
     */
    public function generate($name, $parameters = array(), $absolute = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $debug = array();

        foreach ($this->all() as $router) {
            // if $router does not announce it is capable of handling
            // non-string routes and $name is not a string, continue
            if ($name && !is_string($name) && !$router instanceof VersatileGeneratorInterface) {
                continue;
            }

            // If $router is versatile and doesn't support this route name, continue
            if ($router instanceof VersatileGeneratorInterface && !$router->supports($name)) {
                continue;
            }

            try {
                return $router->generate($name, $parameters, $absolute);
            } catch (RouteNotFoundException $e) {
                $hint = $this->getErrorMessage($name, $router, $parameters);
                $debug[] = $hint;
                if ($this->logger) {
                    $this->logger->debug('Router '.get_class($router)." was unable to generate route. Reason: '$hint': ".$e->getMessage());
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

    /**
     * Rebuild the request object from a URL with the help of the RequestContext.
     *
     * If the request context is not set, this simply returns the request object built from $uri.
     *
     * @param string $pathinfo
     *
     * @return Request
     */
    private function rebuildRequest($pathinfo)
    {
        if (!$this->context) {
            return Request::create('http://localhost'.$pathinfo);
        }

        $uri = $pathinfo;

        $server = array();
        if ($this->context->getBaseUrl()) {
            $uri = $this->context->getBaseUrl().$pathinfo;
            $server['SCRIPT_FILENAME'] = $this->context->getBaseUrl();
            $server['PHP_SELF'] = $this->context->getBaseUrl();
        }
        $host = $this->context->getHost() ?: 'localhost';
        if ('https' === $this->context->getScheme() && 443 !== $this->context->getHttpsPort()) {
            $host .= ':'.$this->context->getHttpsPort();
        }
        if ('http' === $this->context->getScheme() && 80 !== $this->context->getHttpPort()) {
            $host .= ':'.$this->context->getHttpPort();
        }
        $uri = $this->context->getScheme().'://'.$host.$uri.'?'.$this->context->getQueryString();

        return Request::create($uri, $this->context->getMethod(), $this->context->getParameters(), array(), array(), $server);
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
            $this->routeCollection = new ChainRouteCollection();
            foreach ($this->all() as $router) {
                $this->routeCollection->addCollection($router->getRouteCollection());
            }
        }

        return $this->routeCollection;
    }

    /**
     * Identify if any routers have been added into the chain yet.
     *
     * @return bool
     */
    public function hasRouters()
    {
        return !empty($this->routers);
    }
}
