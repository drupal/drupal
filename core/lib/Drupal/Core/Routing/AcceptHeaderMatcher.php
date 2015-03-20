<?php

/**
 * @file
 * Contains Drupal\Core\Routing\AcceptHeaderMatcher.
 */

namespace Drupal\Core\Routing;

use Drupal\Component\Utility\String;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Filters routes based on the media type specified in the HTTP Accept headers.
 */
class AcceptHeaderMatcher implements RouteFilterInterface {

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Generates a list of Symfony formats matching the acceptable MIME types.
    // @todo replace by proper content negotiation library.
    $acceptable_mime_types = $request->getAcceptableContentTypes();
    $acceptable_formats = array_filter(array_map(array($request, 'getFormat'), $acceptable_mime_types));
    $primary_format = $request->getRequestFormat();

    foreach ($collection as $name => $route) {
      // _format could be a |-delimited list of supported formats.
      $supported_formats = array_filter(explode('|', $route->getRequirement('_format')));

      if (empty($supported_formats)) {
        // No format restriction on the route, so it always matches. Move it to
        // the end of the collection by re-adding it.
        $collection->add($name, $route);
      }
      elseif (in_array($primary_format, $supported_formats)) {
        // Perfect match, which will get a higher priority by leaving the route
        // on top of the list.
      }
      // The route partially matches if it doesn't care about format, if it
      // explicitly allows any format, or if one of its allowed formats is
      // in the request's list of acceptable formats.
      elseif (in_array('*/*', $acceptable_mime_types) || array_intersect($acceptable_formats, $supported_formats)) {
        // Move it to the end of the list.
        $collection->add($name, $route);
      }
      else {
        // Remove the route if it does not match at all.
        $collection->remove($name);
      }
    }

    if (count($collection)) {
      return $collection;
    }

    // We do not throw a
    // \Symfony\Component\Routing\Exception\ResourceNotFoundException here
    // because we don't want to return a 404 status code, but rather a 406.
    throw new NotAcceptableHttpException(String::format('No route found for the specified formats @formats.', array('@formats' => implode(' ', $acceptable_mime_types))));
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return TRUE;
  }

}
