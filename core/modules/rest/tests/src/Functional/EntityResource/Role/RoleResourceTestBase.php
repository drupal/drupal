<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Role;

@trigger_error('The ' . __NAMESPACE__ . '\RoleResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\user\Functional\Rest\RoleResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\user\Functional\Rest\RoleResourceTestBase as RoleResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\user\Functional\Rest\RoleResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class RoleResourceTestBase extends RoleResourceTestBaseReal {
}
