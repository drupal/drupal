<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Action;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group rest
 */
class ActionJsonCookieTest extends ActionResourceTestBase {

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
