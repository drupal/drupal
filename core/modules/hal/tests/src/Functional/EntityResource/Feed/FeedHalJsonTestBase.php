<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Feed;

use Drupal\Tests\aggregator\Functional\Hal\FeedHalJsonTestBase as FeedHalJsonTestBaseReal;

/**
 * Class for backward compatibility. It is deprecated in Drupal 8.6.x.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class FeedHalJsonTestBase extends FeedHalJsonTestBaseReal {
}
