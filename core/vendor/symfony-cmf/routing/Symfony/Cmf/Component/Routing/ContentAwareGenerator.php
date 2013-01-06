<?php

namespace Symfony\Cmf\Component\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

use Symfony\Cmf\Component\Routing\RouteProviderInterface;

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
     * @param string $name       ignored
     * @param array  $parameters must either contain the field 'route' with a
     *      RouteObjectInterface or the field 'content' with the document
     *      instance to get the route for (implementing RouteAwareInterface)
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
        $route = $this->provider->getRouteByName($name, $parameters);
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
            $content = $route->getRouteContent();
            if ($content instanceof RouteAwareInterface) {
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
     * Get the route based on the content field in parameters
     *
     * Called in generate when there is no route given in the parameters.
     *
     * If there is more than one route for the content, tries to find the
     * first one that matches the _locale (provided in $parameters or otherwise
     * defaulting to the request locale).
     *
     * If none is found, falls back to just return the first route.
     *
     * @param mixed $name
     * @param array $parameters which should contain a content field containing a RouteAwareInterface object
     *
     * @return SymfonyRoute the route instance
     *
     * @throws RouteNotFoundException if there is no content field in the
     *      parameters or its not possible to build a route from that object
     */
    protected function getRouteByContent($name, &$parameters)
    {
        if ($name instanceof RouteAwareInterface) {
            $content = $name;
        } elseif (isset($parameters['content_id']) && null !== $this->contentRepository) {
            $content = $this->contentRepository->findById($parameters['content_id']);
        } elseif (isset($parameters['content'])) {
            $content = $parameters['content'];
        }

        unset($parameters['content'], $parameters['content_id']);

        if (empty($content)) {
            throw new RouteNotFoundException('Neither the route name, nor a parameter "content" or "content_id" could be resolved to an content instance');
        }

        if (!$content instanceof RouteAwareInterface) {
            $hint = is_object($content) ? get_class($content) : gettype($content);
            throw new RouteNotFoundException('The content does not implement RouteAwareInterface: ' . $hint);
        }

        $routes = $content->getRoutes();
        if (empty($routes)) {
            $hint = method_exists($content, 'getPath') ? $content->getPath() : get_class($content);
            throw new RouteNotFoundException('Document has no route: ' . $hint);
        }

        $route = $this->getRouteByLocale($routes, $this->getLocale($parameters));
        if ($route) {
            return $route;
        }

        // if none matched, continue and randomly return the first one
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
     * @return bool TRUE if there is either no _locale, no _locale requirement or if the two match
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
     * @return string|null the locale following of the parameters or any other
     *  information the router has available.
     */
    protected function getLocale($parameters)
    {
        if (isset($parameters['_locale'])) {
            return $parameters['_locale'];
        }

        return null;
    }

    /**
     * We additionally support empty name and data in parameters and RouteAware content
     */
    public function supports($name)
    {
        return ! $name || parent::supports($name) || $name instanceof RouteAwareInterface;
    }
}
