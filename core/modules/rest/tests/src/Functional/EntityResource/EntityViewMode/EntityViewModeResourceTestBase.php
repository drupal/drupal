<?php

namespace Drupal\Tests\rest\Functional\EntityResource\EntityViewMode;

@trigger_error('The ' . __NAMESPACE__ . '\EntityViewModeResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\FunctionalTests\Rest\EntityViewModeResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\FunctionalTests\Rest\EntityViewModeResourceTestBase as EntityViewModeResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\FunctionalTests\Rest\EntityViewModeResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class EntityViewModeResourceTestBase extends EntityViewModeResourceTestBaseReal {
}
