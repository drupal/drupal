<?php

namespace Drupal\Tests\rest\Functional\EntityResource\FieldStorageConfig;

@trigger_error('The ' . __NAMESPACE__ . '\FieldStorageConfigResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\field\Functional\Rest\FieldStorageConfigResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\field\Functional\Rest\FieldStorageConfigResourceTestBase as FieldStorageConfigResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\field\Functional\Rest\FieldStorageConfigResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class FieldStorageConfigResourceTestBase extends FieldStorageConfigResourceTestBaseReal {
}
