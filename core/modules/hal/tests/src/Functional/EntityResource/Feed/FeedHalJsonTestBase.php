<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Feed;

@trigger_error('The ' . __NAMESPACE__ . '\FeedHalJsonTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\aggregator\Functional\Hal\FeedHalJsonTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\aggregator\Functional\Hal\FeedHalJsonTestBase as FeedHalJsonTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\aggregator\Functional\Hal\FeedHalJsonTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class FeedHalJsonTestBase extends FeedHalJsonTestBaseReal {
}
