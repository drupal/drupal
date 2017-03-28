<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Shortcut;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class ShortcutHalJsonCookieTest extends ShortcutHalJsonAnonTest {

  use CookieResourceTestTrait;
  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
