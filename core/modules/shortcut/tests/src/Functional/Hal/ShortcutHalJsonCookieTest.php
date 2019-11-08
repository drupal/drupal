<?php

namespace Drupal\Tests\shortcut\Functional\Hal;

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

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
