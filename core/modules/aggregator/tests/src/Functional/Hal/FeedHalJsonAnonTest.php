<?php

namespace Drupal\Tests\aggregator\Functional\Hal;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class FeedHalJsonAnonTest extends FeedHalJsonTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

}
