<?php

/**
 * @file
 * Contains Drupal\Core\Routing\MimeTypeMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * This class filters routes based on the media type in HTTP Accept headers.
 */
class MimeTypeMatcher extends PartialMatcher {

  /**
   * Matches a request against multiple routes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A Request object against which to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A RouteCollection of matched routes.
   */
  public function matchRequestPartial(Request $request) {

    // Generates a list of Symfony formats matching the acceptable MIME types.
    // @todo replace by proper content negotiation library.
    $acceptable_mime_types = $request->getAcceptableContentTypes();
    $acceptable_formats = array_map(array($request, 'getFormat'), $acceptable_mime_types);

    $collection = new RouteCollection();

    foreach ($this->routes->all() as $name => $route) {
      // _format could be a |-delimited list of supported formats.
      $supported_formats = array_filter(explode('|', $route->getRequirement('_format')));
      // The route partially matches if it doesn't care about format, if it
      // explicitly allows any format, or if one of its allowed formats is
      // in the request's list of acceptable formats.
      if (empty($supported_formats) || in_array('*/*', $acceptable_mime_types) || array_intersect($acceptable_formats, $supported_formats)) {
        $collection->add($name, $route);
      }
    }

    if (!count($collection)) {
      throw new UnsupportedMediaTypeHttpException();
    }

    return $collection;
  }

}
