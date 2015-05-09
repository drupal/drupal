<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\ArgumentsResolverInterface.
 */

namespace Drupal\Component\Utility;

/**
 * Resolves the arguments to pass to a callable.
 */
interface ArgumentsResolverInterface {

  /**
   * Gets arguments suitable for passing to the given callable.
   *
   * @return array
   *   An array of arguments to pass to the callable.
   *
   * @throws \RuntimeException
   *   When a value for an argument given cannot be resolved.
   */
  public function getArguments(callable $callable);

}
