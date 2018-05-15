<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Item;

@trigger_error('The ' . __NAMESPACE__ . '\ItemResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\aggregator\Functional\Rest\ItemResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\aggregator\Functional\Rest\ItemResourceTestBase as ItemResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\aggregator\Functional\Rest\ItemResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ItemResourceTestBase extends ItemResourceTestBaseReal {
}
