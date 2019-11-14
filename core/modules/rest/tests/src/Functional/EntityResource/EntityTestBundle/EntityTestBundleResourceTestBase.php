<?php

namespace Drupal\Tests\rest\Functional\EntityResource\EntityTestBundle;

@trigger_error('The ' . __NAMESPACE__ . '\EntityTestBundleResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\entity_test\Functional\Rest\EntityTestBundleResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\entity_test\Functional\Rest\EntityTestBundleResourceTestBase as EntityTestBundleResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\entity_test\Functional\Rest\EntityTestBundleResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class EntityTestBundleResourceTestBase extends EntityTestBundleResourceTestBaseReal {
}
