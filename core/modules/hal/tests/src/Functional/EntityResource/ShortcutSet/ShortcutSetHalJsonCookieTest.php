<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ShortcutSet;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class ShortcutSetHalJsonCookieTest extends ShortcutSetHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
