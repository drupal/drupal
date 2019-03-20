<?php

namespace Drupal\jsonapi\Routing;

use Drupal\Core\Routing\RequestFormatRouteFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the 'api_json' format for requests to JSON:API resources.
 *
 * Because this module places all JSON:API resources at paths prefixed with
 * /jsonapi, and therefore not shared with other formats,
 * \Drupal\Core\Routing\RequestFormatRouteFilter does correctly set the request
 * format for those requests. However, it does so after other filters, such as
 * \Drupal\Core\Routing\ContentTypeHeaderMatcher, run. If those other filters
 * throw exceptions, we'd like the error response to be in JSON:API format as
 * well, so we set that format here, in a higher priority (earlier running)
 * filter. This works so long as the resource format can be determined before
 * running any other filters, which is the case for JSON:API resources per
 * above.
 *
 * @internal
 */
final class EarlyFormatSetter extends RequestFormatRouteFilter {

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    if (is_null($request->getRequestFormat(NULL))) {
      $possible_formats = static::getAvailableFormats($collection);
      if ($possible_formats === ['api_json']) {
        $request->setRequestFormat('api_json');
      }
    }
    return $collection;
  }

}
