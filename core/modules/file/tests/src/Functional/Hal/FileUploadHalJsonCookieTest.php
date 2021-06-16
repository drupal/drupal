<?php

namespace Drupal\Tests\file\Functional\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class FileUploadHalJsonCookieTest extends FileUploadHalJsonTestBase {

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
