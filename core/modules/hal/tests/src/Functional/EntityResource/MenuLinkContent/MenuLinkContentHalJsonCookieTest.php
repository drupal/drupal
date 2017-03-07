<?php

namespace Drupal\Tests\hal\Functional\EntityResource\MenuLinkContent;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class MenuLinkContentHalJsonCookieTest extends MenuLinkContentHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
