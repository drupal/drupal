<?php

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;

/**
 * An access check service determines access rules for particular routes.
 */
interface AccessCheckInterface extends RoutingAccessInterface {

  /**
   * Declares whether the access check applies to a specific route or not.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to consider attaching to.
   *
   * @return bool
   *   TRUE if this access checker applies to this route.
   */
  public function applies(Route $route);

}
