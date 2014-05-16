<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessArgumentsResolverInterface.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the arguments to pass to an access check callable.
 */
interface AccessArgumentsResolverInterface {

  /**
   * Returns the arguments to pass to the access check callable.
   *
   * @param callable $callable
   *   A PHP callable.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access to.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return array
   *   An array of arguments to pass to the callable.
   *
   * @throws \RuntimeException
   *   When a value for an argument given is not provided.
   */
  public function getArguments(callable $callable, Route $route, Request $request, AccountInterface $account);

}
