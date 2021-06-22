<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Filters routes based on the HTTP Content-type header.
 */
class ContentTypeHeaderMatcher implements FilterInterface {

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    // The Content-type header does not make sense on GET or DELETE requests,
    // because they do not carry any content. Nothing to filter in this case.
    // Same for all other safe methods.
    if ($request->isMethodSafe() || $request->isMethod('DELETE')) {
      return $collection;
    }

    $format = $request->getContentType();

    foreach ($collection as $name => $route) {
      $supported_formats = array_filter(explode('|', $route->getRequirement('_content_type_format') ?? ''));
      if (empty($supported_formats)) {
        // No restriction on the route, so we move the route to the end of the
        // collection by re-adding it. That way generic routes sink down in the
        // list and exact matching routes stay on top.
        $collection->add($name, $route);
      }
      elseif (!in_array($format, $supported_formats)) {
        $collection->remove($name);
      }
    }
    if (count($collection)) {
      return $collection;
    }
    // We do not throw a
    // \Symfony\Component\Routing\Exception\ResourceNotFoundException here
    // because we don't want to return a 404 status code, but rather a 415.
    if (!$request->headers->get('Content-Type', FALSE)) {
      throw new UnsupportedMediaTypeHttpException('No "Content-Type" request header specified');
    }
    else {
      throw new UnsupportedMediaTypeHttpException('No route found that matches "Content-Type: ' . $request->headers->get('Content-Type') . '"');
    }
  }

}
