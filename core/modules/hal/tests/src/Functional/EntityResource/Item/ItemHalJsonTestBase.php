<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Item;

@trigger_error('The ' . __NAMESPACE__ . '\ItemHalJsonTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\aggregator\Functional\Hal\ItemHalJsonTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\aggregator\Functional\Hal\ItemHalJsonTestBase as ItemHalJsonTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\aggregator\Functional\Hal\ItemHalJsonTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ItemHalJsonTestBase extends ItemHalJsonTestBaseReal {
}
