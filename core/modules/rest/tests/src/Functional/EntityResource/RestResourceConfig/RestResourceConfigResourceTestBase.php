<?php

namespace Drupal\Tests\rest\Functional\EntityResource\RestResourceConfig;

@trigger_error('The ' . __NAMESPACE__ . '\RestResourceConfigResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\rest\Functional\Rest\RestResourceConfigResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\rest\Functional\Rest\RestResourceConfigResourceTestBase as RestResourceConfigResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\rest\Functional\Rest\RestResourceConfigResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class RestResourceConfigResourceTestBase extends RestResourceConfigResourceTestBaseReal {
}
