<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Feed;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class FeedHalJsonCookieTest extends FeedHalJsonTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
