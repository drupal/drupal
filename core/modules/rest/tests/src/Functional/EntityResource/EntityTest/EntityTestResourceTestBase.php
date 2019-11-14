<?php

namespace Drupal\Tests\rest\Functional\EntityResource\EntityTest;

@trigger_error('The ' . __NAMESPACE__ . '\EntityTestResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\entity_test\Functional\Rest\EntityTestResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\entity_test\Functional\Rest\EntityTestResourceTestBase as EntityTestResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\entity_test\Functional\Rest\EntityTestResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class EntityTestResourceTestBase extends EntityTestResourceTestBaseReal {
}
