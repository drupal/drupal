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

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher as SymfonyUrlMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Extended UrlMatcher to provide an additional interface and enhanced features.
 *
 * This class requires Symfony 2.2 for a refactoring done to the symfony UrlMatcher
 *
 * @author Larry Garfield
 */
class UrlMatcher extends SymfonyUrlMatcher implements FinalMatcherInterface
{
    /**
     * {@inheritdoc}
     */
    public function finalMatch(RouteCollection $collection, Request $request)
    {
        $this->routes = $collection;
        $context = new RequestContext();
        $context->fromRequest($request);
        $this->setContext($context);

        return $this->match($request->getPathInfo());
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes(Route $route, $name, array $attributes)
    {
        if ($route instanceof RouteObjectInterface && is_string($route->getRouteKey())) {
            $name = $route->getRouteKey();
        }
        $attributes[RouteObjectInterface::ROUTE_NAME] = $name;
        $attributes[RouteObjectInterface::ROUTE_OBJECT] = $route;

        return $this->mergeDefaults($attributes, $route->getDefaults());
    }
}
