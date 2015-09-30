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

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;

/**
 * A generator that tries to generate routes from object, route names or
 * content objects or names.
 *
 * @author Philippo de Santis
 * @author David Buchmann
 * @author Uwe Jäger
 */
class ContentAwareGenerator extends ProviderBasedGenerator
{
    /**
     * The locale to use when neither the parameters nor the request context
     * indicate the locale to use.
     *
     * @var string
     */
    protected $defaultLocale = null;

    /**
     * The content repository used to find content by it's id
     * This can be used to specify a parameter content_id when generating urls
     *
     * This is optional and might not be initialized.
     *
     * @var  ContentRepositoryInterface
     */
    protected $contentRepository;

    /**
     * Set an optional content repository to find content by ids
     *
     * @param ContentRepositoryInterface $contentRepository
     */
    public function setContentRepository(ContentRepositoryInterface $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name       ignored.
     * @param array  $parameters must either contain the field 'route' with a
     *                           RouteObjectInterface or the field 'content_id'
     *                           with the id of a document implementing
     *                           RouteReferrersReadInterface.
     *
     * @throws RouteNotFoundException If there is no such route in the database
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        if ($name instanceof SymfonyRoute) {
            $route = $this->getBestLocaleRoute($name, $parameters);
        } elseif (is_string($name) && $name) {
            $route = $this->getRouteByName($name, $parameters);
        } else {
            $route = $this->getRouteByContent($name, $parameters);
        }

        if (! $route instanceof SymfonyRoute) {
            $hint = is_object($route) ? get_class($route) : gettype($route);
            throw new RouteNotFoundException('Route of this document is not an instance of Symfony\Component\Routing\Route but: '.$hint);
        }

        $this->unsetLocaleIfNotNeeded($route, $parameters);

        return parent::generate($route, $parameters, $absolute);
    }

    /**
     * Get the route by a string name
     *
     * @param string $route
     * @param array  $parameters
     *
     * @return SymfonyRoute
     *
     * @throws RouteNotFoundException if there is no route found for the provided name
     */
    protected function getRouteByName($name, array $parameters)
    {
        $route = $this->provider->getRouteByName($name);
        if (empty($route)) {
            throw new RouteNotFoundException('No route found for name: ' . $name);
        }

        return $this->getBestLocaleRoute($route, $parameters);
    }

    /**
     * Determine if there is a route with matching locale associated with the
     * given route via associated content.
     *
     * @param SymfonyRoute $route
     * @param array        $parameters
     *
     * @return SymfonyRoute either the passed route or an alternative with better locale
     */
    protected function getBestLocaleRoute(SymfonyRoute $route, $parameters)
    {
        if (! $route instanceof RouteObjectInterface) {
            // this route has no content, we can't get the alternatives
            return $route;
        }
        $locale = $this->getLocale($parameters);
        if (! $this->checkLocaleRequirement($route, $locale)) {
            $content = $route->getContent();
            if ($content instanceof RouteReferrersReadInterface) {
                $routes = $content->getRoutes();
                $contentRoute = $this->getRouteByLocale($routes, $locale);
                if ($contentRoute) {
                    return $contentRoute;
                }
            }
        }

        return $route;
    }

    /**
     * Get the route based on the $name that is an object implementing
     * RouteReferrersReadInterface or a content found in the content repository
     * with the content_id specified in parameters that is an instance of
     * RouteReferrersReadInterface.
     *
     * Called in generate when there is no route given in the parameters.
     *
     * If there is more than one route for the content, tries to find the
     * first one that matches the _locale (provided in $parameters or otherwise
     * defaulting to the request locale).
     *
     * If no route with matching locale is found, falls back to just return the
     * first route.
     *
     * @param mixed $name
     * @param array $parameters which should contain a content field containing
     *                          a RouteReferrersReadInterface object
     *
     * @return SymfonyRoute the route instance
     *
     * @throws RouteNotFoundException if no route can be determined
     */
    protected function getRouteByContent($name, &$parameters)
    {
        if ($name instanceof RouteReferrersReadInterface) {
            $content = $name;
        } elseif (isset($parameters['content_id'])
            && null !== $this->contentRepository
        ) {
            $content = $this->contentRepository->findById($parameters['content_id']);
            if (empty($content)) {
                throw new RouteNotFoundException('The content repository found nothing at id ' . $parameters['content_id']);
            }
            if (!$content instanceof RouteReferrersReadInterface) {
                throw new RouteNotFoundException('Content repository did not return a RouteReferrersReadInterface instance for id ' . $parameters['content_id']);
            }
        } else {
            $hint = is_object($name) ? get_class($name) : gettype($name);
            throw new RouteNotFoundException("The route name argument '$hint' is not RouteReferrersReadInterface instance and there is no 'content_id' parameter");
        }

        $routes = $content->getRoutes();
        if (empty($routes)) {
            $hint = ($this->contentRepository && $this->contentRepository->getContentId($content))
                ? $this->contentRepository->getContentId($content)
                : get_class($content);
            throw new RouteNotFoundException('Content document has no route: ' . $hint);
        }

        unset($parameters['content_id']);

        $route = $this->getRouteByLocale($routes, $this->getLocale($parameters));
        if ($route) {
            return $route;
        }

        // if none matched, randomly return the first one
        if ($routes instanceof Collection) {
            return $routes->first();
        }

        return reset($routes);
    }

    /**
     * @param RouteCollection $routes
     * @param string          $locale
     *
     * @return bool|SymfonyRoute false if no route requirement matches the provided locale
     */
    protected function getRouteByLocale($routes, $locale)
    {
        foreach ($routes as $route) {
            if (! $route instanceof SymfonyRoute) {
                continue;
            }

            if ($this->checkLocaleRequirement($route, $locale)) {
                return $route;
            }
        }

        return false;
    }

    /**
     * @param SymfonyRoute $route
     * @param string       $locale
     *
     * @return bool true if there is either no $locale, no _locale requirement
     *              on the route or if the requirement and the passed $locale
     *              match.
     */
    private function checkLocaleRequirement(SymfonyRoute $route, $locale)
    {
        return empty($locale)
            || !$route->getRequirement('_locale')
            || preg_match('/'.$route->getRequirement('_locale').'/', $locale)
        ;
    }

    /**
     * Determine the locale to be used with this request
     *
     * @param array $parameters the parameters determined by the route
     *
     * @return string the locale following of the parameters or any other
     *                information the router has available. defaultLocale if no
     *                other locale can be determined.
     */
    protected function getLocale($parameters)
    {
        if (isset($parameters['_locale'])) {
            return $parameters['_locale'];
        }

        if ($this->getContext()->hasParameter('_locale')) {
            return $this->getContext()->getParameter('_locale');
        }

        return $this->defaultLocale;
    }

    /**
     * Overwrite the locale to be used by default if there is neither one in
     * the parameters when building the route nor a request available (i.e. CLI).
     *
     * @param string $locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * We additionally support empty name and data in parameters and RouteAware content
     */
    public function supports($name)
    {
        return ! $name || parent::supports($name) || $name instanceof RouteReferrersReadInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteDebugMessage($name, array $parameters = array())
    {
        if (empty($name) && isset($parameters['content_id'])) {
            return 'Content id ' . $parameters['content_id'];
        }

        if ($name instanceof RouteReferrersReadInterface) {
            return 'Route aware content ' . parent::getRouteDebugMessage($name, $parameters);
        }

        return parent::getRouteDebugMessage($name, $parameters);
    }

    /**
     * If the _locale parameter is allowed by the requirements of the route
     * and it is the default locale, remove it from the parameters so that we
     * do not get an unneeded ?_locale= query string.
     *
     * @param SymfonyRoute $route      The route being generated.
     * @param array        $parameters The parameters used, will be modified to
     *                                 remove the _locale field if needed.
     */
    protected function unsetLocaleIfNotNeeded(SymfonyRoute $route, array &$parameters)
    {
        $locale = $this->getLocale($parameters);
        if (null !== $locale) {
            if (preg_match('/'.$route->getRequirement('_locale').'/', $locale)
                && $locale == $route->getDefault('_locale')
            ) {
                $compiledRoute = $route->compile();
                if (!in_array('_locale', $compiledRoute->getVariables())) {
                    unset($parameters['_locale']);
                }
            }
        }
    }
}
