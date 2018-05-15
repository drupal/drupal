<?php

namespace Drupal\Tests\rest\Functional\EntityResource\User;

@trigger_error('The ' . __NAMESPACE__ . '\UserResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\user\Functional\Rest\UserResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\user\Functional\Rest\UserResourceTestBase as UserResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\user\Functional\Rest\UserResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class UserResourceTestBase extends UserResourceTestBaseReal {
}
