<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a route filter, which filters by the request format.
 */
class RequestFormatRouteFilter implements FilterInterface {

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Determine the request format.
    $default_format = static::getDefaultFormat($collection);
    // If the request does not specify a format then use the default.
    if (is_null($request->getRequestFormat(NULL))) {
      $format = $default_format;
      $request->setRequestFormat($default_format);
    }
    else {
      $format = $request->getRequestFormat($default_format);
    }

    $routes_with_requirement = [];
    $routes_without_requirement = [];
    $result_collection = new RouteCollection();
    /** @var \Symfony\Component\Routing\Route $route */
    foreach ($collection as $name => $route) {
      if (!$route->hasRequirement('_format')) {
        $routes_without_requirement[$name] = $route;
        continue;
      }
      else {
        $routes_with_requirement[$name] = $route;
      }
    }

    foreach ($routes_with_requirement as $name => $route) {
      // If the route has no _format specification, we move it to the end. If it
      // does, then no match means the route is removed entirely.
      if (($supported_formats = array_filter(explode('|', $route->getRequirement('_format')))) && in_array($format, $supported_formats, TRUE)) {
        $result_collection->add($name, $route);
      }
    }

    foreach ($routes_without_requirement as $name => $route) {
      $result_collection->add($name, $route);
    }

    if (count($result_collection)) {
      return $result_collection;
    }

    // We do not throw a
    // \Symfony\Component\Routing\Exception\ResourceNotFoundException here
    // because we don't want to return a 404 status code, but rather a 406.
    throw new NotAcceptableHttpException("No route found for the specified format $format.");
  }

  /**
   * Determines the default request format.
   *
   * By default, use 'html' as the default format. But when there's only a
   * single route match, and that route specifies a '_format' requirement
   * listing a single format, then use that as the default format. Also, if
   * there are multiple routes which all require the same single format then
   * use it.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to filter.
   *
   * @return string
   *   The default format.
   */
  protected static function getDefaultFormat(RouteCollection $collection) {
    // Get the set of formats across all routes in the collection.
    $all_formats = array_reduce($collection->all(), function (array $carry, Route $route) {
      // Routes without a '_format' requirement are assumed to require HTML.
      $route_formats = !$route->hasRequirement('_format')
        ? ['html']
        : explode('|', $route->getRequirement('_format'));
      return array_merge($carry, $route_formats);
    }, []);
    $formats = array_unique(array_filter($all_formats));

    // The default format is 'html' unless ALL routes require the same format.
    return count($formats) === 1
      ? reset($formats)
      : 'html';
  }

}
