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

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Psr\Log\LoggerInterface;

/**
 * A Generator that uses a RouteProvider rather than a RouteCollection
 *
 * @author Larry Garfield
 */
class ProviderBasedGenerator extends UrlGenerator implements VersatileGeneratorInterface
{
    /**
     * The route provider for this generator.
     *
     * @var RouteProviderInterface
     */
    protected $provider;

    /**
     * @param RouteProviderInterface $provider
     * @param LoggerInterface        $logger
     */
    public function __construct(RouteProviderInterface $provider, LoggerInterface $logger = null)
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->context = new RequestContext();
    }

    /**
     * {@inheritDoc}
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        if ($name instanceof SymfonyRoute) {
            $route = $name;
        } elseif (null === $route = $this->provider->getRouteByName($name, $parameters)) {
            throw new RouteNotFoundException(sprintf('Route "%s" does not exist.', $name));
        }

        // the Route has a cache of its own and is not recompiled as long as it does not get modified
        $compiledRoute = $route->compile();
        $hostTokens = $compiledRoute->getHostTokens();

        $debug_message = $this->getRouteDebugMessage($name);

        return $this->doGenerate($compiledRoute->getVariables(), $route->getDefaults(), $route->getRequirements(), $compiledRoute->getTokens(), $parameters, $debug_message, $absolute, $hostTokens);
    }

    /**
     * Support a route object and any string as route name
     *
     * {@inheritDoc}
     */
    public function supports($name)
    {
        return is_string($name) || $name instanceof SymfonyRoute;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteDebugMessage($name, array $parameters = array())
    {
        if (is_scalar($name)) {
            return $name;
        }

        if (is_array($name)) {
            return serialize($name);
        }

        if ($name instanceof RouteObjectInterface) {
            return 'Route with key ' . $name->getRouteKey();
        }

        if ($name instanceof SymfonyRoute) {
            return 'Route with pattern ' . $name->getPattern();
        }

        return get_class($name);
    }

}
