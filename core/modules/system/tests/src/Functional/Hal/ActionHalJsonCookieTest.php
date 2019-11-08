<?php

namespace Drupal\Tests\system\Functional\Hal;

use Drupal\Tests\system\Functional\Rest\ActionResourceTestBase;
use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class ActionHalJsonCookieTest extends ActionResourceTestBase {

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
