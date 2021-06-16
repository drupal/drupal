<?php

namespace Drupal\Tests\node\Functional\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class NodeHalJsonCookieTest extends NodeHalJsonAnonTest {

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
