<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Menu;

@trigger_error('The ' . __NAMESPACE__ . '\MenuResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\system\Functional\Rest\MenuResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\system\Functional\Rest\MenuResourceTestBase as MenuResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\system\Functional\Rest\MenuResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class MenuResourceTestBase extends MenuResourceTestBaseReal {
}
