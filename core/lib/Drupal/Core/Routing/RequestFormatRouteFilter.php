<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a route filter, which filters by the request format.
 */
class RequestFormatRouteFilter implements RouteFilterInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement('_format');
  }

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Determine the request format.
    $default_format = static::getDefaultFormat($collection);
    $format = $request->getRequestFormat($default_format);

    /** @var \Symfony\Component\Routing\Route $route */
    foreach ($collection as $name => $route) {
      // If the route has no _format specification, we move it to the end. If it
      // does, then no match means the route is removed entirely.
      if ($supported_formats = array_filter(explode('|', $route->getRequirement('_format')))) {
        if (!in_array($format, $supported_formats)) {
          $collection->remove($name);
        }
      }
      else {
        $collection->add($name, $route);
      }
    }

    if (count($collection)) {
      return $collection;
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
   * listing a single format, then use that as the default format.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to filter.
   *
   * @return string
   *   The default format.
   */
  protected static function getDefaultFormat(RouteCollection $collection) {
    $default_format = 'html';
    if ($collection->count() === 1) {
      $only_route = $collection->getIterator()->current();
      $required_format = $only_route->getRequirement('_format');
      if (strpos($required_format, '|') === FALSE) {
        $default_format = $required_format;
      }
    }
    return $default_format;
  }

}
