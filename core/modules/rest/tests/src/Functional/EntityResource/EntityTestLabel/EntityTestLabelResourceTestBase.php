<?php

namespace Drupal\Tests\rest\Functional\EntityResource\EntityTestLabel;

@trigger_error('The ' . __NAMESPACE__ . '\EntityTestLabelResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\entity_test\Functional\Rest\EntityTestLabelResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\entity_test\Functional\Rest\EntityTestLabelResourceTestBase as EntityTestLabelResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\entity_test\Functional\Rest\EntityTestLabelResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class EntityTestLabelResourceTestBase extends EntityTestLabelResourceTestBaseReal {
}
