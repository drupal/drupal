<?php

namespace Symfony\Cmf\Component\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

use Symfony\Cmf\Component\Routing\RouteProviderInterface;

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

    public function __construct(RouteProviderInterface $provider, LoggerInterface $logger = null)
    {
        $this->provider = $provider;
        $this->logger = $logger;
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

        // handle symfony 2.1 and 2.2
        // getHostTokens exists only since 2.2
        $hostTokens = null;
        if (method_exists($compiledRoute, 'getHostTokens')) {
            $hostTokens = $compiledRoute->getHostTokens();
        }

        return $this->doGenerate($compiledRoute->getVariables(), $route->getDefaults(), $route->getRequirements(), $compiledRoute->getTokens(), $parameters, $name, $absolute, $hostTokens);
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
}
