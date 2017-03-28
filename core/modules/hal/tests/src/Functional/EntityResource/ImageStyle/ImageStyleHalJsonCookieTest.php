<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ImageStyle;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ImageStyle\ImageStyleResourceTestBase;

/**
 * @group rest
 */
class ImageStyleHalJsonCookieTest extends ImageStyleResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

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
