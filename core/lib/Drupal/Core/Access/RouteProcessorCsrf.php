<?php

/**
 * @file
 * Contains \Drupal\Core\Access\RouteProcessorCsrf.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Symfony\Component\Routing\Route;

/**
 * Processes the outbound route to handle the CSRF token.
 */
class RouteProcessorCsrf implements OutboundRouteProcessorInterface {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs a RouteProcessorCsrf object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  function __construct(CsrfTokenGenerator $csrf_token) {
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, CacheableMetadata $cacheable_metadata = NULL) {
    if ($route->hasRequirement('_csrf_token')) {
      $path = ltrim($route->getPath(), '/');
      // Replace the path parameters with values from the parameters array.
      foreach ($parameters as $param => $value) {
        $path = str_replace("{{$param}}", $value, $path);
      }
      // Adding this to the parameters means it will get merged into the query
      // string when the route is compiled.
      $parameters['token'] = $this->csrfToken->get($path);
      if ($cacheable_metadata) {
        // Tokens are per user and per session, so not cacheable.
        // @todo Improve in https://www.drupal.org/node/2351015.
        $cacheable_metadata->setCacheMaxAge(0);
      }
    }
  }

}
