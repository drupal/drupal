<?php

namespace Drupal\Tests\layout_builder\Functional\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group layout_builder
 * @group rest
 */
class LayoutBuilderEntityViewDisplayHalJsonCookieTest extends LayoutBuilderEntityViewDisplayHalJsonAnonTest {

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
