<?php

namespace Drupal\Tests\responsive_image\Functional\Hal;

use Drupal\Tests\responsive_image\Functional\Rest\ResponsiveImageStyleResourceTestBase;
use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class ResponsiveImageStyleHalJsonCookieTest extends ResponsiveImageStyleResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
