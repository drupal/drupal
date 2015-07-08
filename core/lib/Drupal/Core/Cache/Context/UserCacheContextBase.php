<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\UserCacheContextBase.
 */

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Session\AccountInterface;

/**
 * Base class for user-based cache contexts.
 *
 * Subclasses need to implement either
 * \Drupal\Core\Cache\Context\CacheContextInterface or
 * \Drupal\Core\Cache\Context\CalculatedCacheContextInterface.
 */
abstract class UserCacheContextBase {

  /**
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a new UserCacheContextBase class.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(AccountInterface $user) {
    $this->user = $user;
  }

}
