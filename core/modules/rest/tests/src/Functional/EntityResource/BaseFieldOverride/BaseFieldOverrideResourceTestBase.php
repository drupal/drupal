<?php

namespace Drupal\Tests\rest\Functional\EntityResource\BaseFieldOverride;

@trigger_error('The ' . __NAMESPACE__ . '\BaseFieldOverrideResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\FunctionalTests\Rest\BaseFieldOverrideResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\FunctionalTests\Rest\BaseFieldOverrideResourceTestBase as BaseFieldOverrideResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\FunctionalTests\Rest\BaseFieldOverrideResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class BaseFieldOverrideResourceTestBase extends BaseFieldOverrideResourceTestBaseReal {
}
