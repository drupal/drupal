<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Message;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group rest
 */
class MessageJsonCookieTest extends MessageResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
