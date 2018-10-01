<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\UserCreationTrait is deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use Drupal\Tests\user\Traits\UserCreationTrait instead. See https://www.drupal.org/node/2884454.', E_USER_DEPRECATED);

use Drupal\Tests\user\Traits\UserCreationTrait as BaseUserCreationTrait;

/**
 * Provides methods to create additional test users and switch the currently
 * logged in one.
 *
 * This trait is meant to be used only by test classes extending
 * \Drupal\simpletest\TestBase.
 *
 * @deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\user\Traits\UserCreationTrait instead.
 *
 * @see https://www.drupal.org/node/2884454
 */
trait UserCreationTrait {

  use BaseUserCreationTrait;

}
