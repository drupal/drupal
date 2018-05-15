<?php

namespace Drupal\Tests\rest\Functional\EntityResource\EntityViewDisplay;

@trigger_error('The ' . __NAMESPACE__ . '\EntityViewDisplayResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\FunctionalTests\Rest\EntityViewDisplayResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\FunctionalTests\Rest\EntityViewDisplayResourceTestBase as EntityViewDisplayResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\FunctionalTests\Rest\EntityViewDisplayResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class EntityViewDisplayResourceTestBase extends EntityViewDisplayResourceTestBaseReal {
}
