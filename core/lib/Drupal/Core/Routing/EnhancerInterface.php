<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * A route enhance service to determine route enhance rules.
 */
interface EnhancerInterface {

  /**
   * Updates the defaults for a route definition based on the request.
   *
   * @param array $defaults
   *   The defaults, maps to '_defaults' in the route definition YAML.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return array
   *   The modified defaults. Each enhancer MUST return the
   *   $defaults but may add or remove values.
   */
  public function enhance(array $defaults, Request $request);

}
