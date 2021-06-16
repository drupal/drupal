<?php

namespace Drupal\Tests\block_content\Functional\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class BlockContentHalJsonCookieTest extends BlockContentHalJsonAnonTest {

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
