<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Feed;

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
