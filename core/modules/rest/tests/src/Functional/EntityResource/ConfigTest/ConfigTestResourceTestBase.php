<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ConfigTest;

@trigger_error('The ' . __NAMESPACE__ . '\ConfigTestResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\config_test\Functional\Rest\ConfigTestResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\config_test\Functional\Rest\ConfigTestResourceTestBase as ConfigTestResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\config_test\Functional\Rest\ConfigTestResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ConfigTestResourceTestBase extends ConfigTestResourceTestBaseReal {
}
