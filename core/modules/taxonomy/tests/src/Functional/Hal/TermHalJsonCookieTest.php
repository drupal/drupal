<?php

namespace Drupal\Tests\taxonomy\Functional\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class TermHalJsonCookieTest extends TermHalJsonAnonTest {

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
