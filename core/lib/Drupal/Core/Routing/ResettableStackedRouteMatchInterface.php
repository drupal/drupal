<?php

namespace Drupal\Core\Routing;

/**
 * Defines an interface for a resettable stack of route matches.
 *
 * @todo Move this method to \Drupal\Core\Routing\StackedRouteMatchInterface in
 *   https://www.drupal.org/node/2659952.
 */
interface ResettableStackedRouteMatchInterface extends StackedRouteMatchInterface {

  /**
   * Resets the route match static cache.
   *
   * The route match should only be statically cached once routing is finished.
   * Any code that uses a route match during routing may be incorrectly assumed
   * to be acting after routing has completed. This method gives that code the
   * ability to fix the static cache.
   */
  public function resetRouteMatch();

}
