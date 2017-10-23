<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Message;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class MessageHalJsonCookieTest extends MessageHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
