<?php

namespace Drupal\simpletest;

use Drupal\Tests\user\Traits\UserCreationTrait as BaseUserCreationTrait;

/**
 * Provides methods to create additional test users and switch the currently
 * logged in one.
 *
 * This trait is meant to be used only by test classes extending
 * \Drupal\simpletest\TestBase.
 *
 * @deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use
<<<<<<< HEAD
 *   Drupal\Tests\user\Traits\UserCreationTrait instead.
=======
 *   \Drupal\Tests\user\Traits\UserCreationTrait instead.
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
 *
 * @see https://www.drupal.org/node/2884454
 */
trait UserCreationTrait {

  use BaseUserCreationTrait;

}
