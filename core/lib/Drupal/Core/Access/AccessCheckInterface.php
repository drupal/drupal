<?php

/**
 * @file
 * Contains Drupal\Core\Access\AccessCheckInterface.
 */

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;

/**
 * An access check service determines access rules for particular routes.
 */
interface AccessCheckInterface extends AccessInterface {

  /**
   * Declares whether the access check applies to a specific route or not.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to consider attaching to.
   *
   * @return array
   *   An array of route requirement keys this access checker applies to.
   */
  public function applies(Route $route);

}
