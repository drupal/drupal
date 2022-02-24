<?php

namespace Drupal\Tests\hal\Functional\shortcut;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 * @group legacy
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
