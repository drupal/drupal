<?php

/**
 * @file
 * Contains Drupal\Core\Routing\MimeTypeMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;

/**
 * This class filters routes based on the media type in HTTP Accept headers.
 */
class MimeTypeMatcher implements RouteFilterInterface {


  /**
   * Implements \Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface::filter()
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Generates a list of Symfony formats matching the acceptable MIME types.
    // @todo replace by proper content negotiation library.
    $acceptable_mime_types = $request->getAcceptableContentTypes();
    $acceptable_formats = array_map(array($request, 'getFormat'), $acceptable_mime_types);

    $filtered_collection = new RouteCollection();

    foreach ($collection as $name => $route) {
      // _format could be a |-delimited list of supported formats.
      $supported_formats = array_filter(explode('|', $route->getRequirement('_format')));
      // The route partially matches if it doesn't care about format, if it
      // explicitly allows any format, or if one of its allowed formats is
      // in the request's list of acceptable formats.
      if (empty($supported_formats) || in_array('*/*', $acceptable_mime_types) || array_intersect($acceptable_formats, $supported_formats)) {
        $filtered_collection->add($name, $route);
      }
    }

    if (!count($filtered_collection)) {
      throw new NotAcceptableHttpException();
    }

    return $filtered_collection;
  }

}
