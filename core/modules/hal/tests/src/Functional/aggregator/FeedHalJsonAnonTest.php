<?php

namespace Drupal\Tests\hal\Functional\aggregator;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 * @group legacy
 */
class FeedHalJsonAnonTest extends FeedHalJsonTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
