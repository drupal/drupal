<?php

namespace Drupal\Tests\rest\Functional\EntityResource\FieldConfig;

@trigger_error('The ' . __NAMESPACE__ . '\FieldConfigResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\field\Functional\Rest\FieldConfigResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\field\Functional\Rest\FieldConfigResourceTestBase as FieldConfigResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\field\Functional\Rest\FieldConfigResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class FieldConfigResourceTestBase extends FieldConfigResourceTestBaseReal {
}
