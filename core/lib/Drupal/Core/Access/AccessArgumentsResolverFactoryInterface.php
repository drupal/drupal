<?php

namespace Drupal\Core\Access;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Constructs the arguments resolver instance to use when running access checks.
 */
interface AccessArgumentsResolverFactoryInterface {

  /**
   * Returns the arguments resolver to use when running access checks.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Optional, the request object.
   *
   * @return \Drupal\Component\Utility\ArgumentsResolverInterface
   *   The parametrized arguments resolver instance.
   */
  public function getArgumentsResolver(RouteMatchInterface $route_match, AccountInterface $account, ?Request $request = NULL);

}
