<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Route;

/**
 * A backwards compatibility route.
 *
 * When a route is deprecated for another one, and backwards compatibility is
 * provided, then it's best practice to:
 * - not duplicate all route definition metadata, to instead have an "as empty
 *   as possible" route
 * - have an accompanying outbound route processor, that overwrites this empty
 *   route definition with the redirected route's definition.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use route
 * aliases instead.
 *
 * @see https://www.drupal.org/node/3317784
 */
class BcRoute extends Route {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct('');
    $this->setOption('bc_route', TRUE);
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use route aliases instead. See https://www.drupal.org/node/3317784', E_USER_DEPRECATED);
  }

}
