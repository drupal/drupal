<?php

/**
 * @file
 * Contains \Drupal\Core\Access\StaticAccessCheckInterface.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;

/**
 * An access check service determines access rules for particular routes.
 *
 * This interface is specifically for routes that know exactly which requirement
 * keys they should react to for a route.
 */
interface StaticAccessCheckInterface extends RoutingAccessInterface {

  /**
   * Declares the route requirement keys this access checker applies to.
   *
   * This should be used when the requirement matching for a route is static,
   * and does not require any further information. For example, '_access' will
   * provide TRUE, or FALSE. We do not need any more information other than the
   * route provides this requirement key.
   *
   * @return array
   *   An array of route requirement keys this access checker applies to. An
   *   empty array will check all routes using the apply method.
   */
  public function appliesTo();

}
