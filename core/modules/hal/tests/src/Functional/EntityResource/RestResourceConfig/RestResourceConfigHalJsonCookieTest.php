<?php

namespace Drupal\Tests\hal\Functional\EntityResource\RestResourceConfig;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\RestResourceConfig\RestResourceConfigResourceTestBase;

/**
 * @group hal
 */
class RestResourceConfigHalJsonCookieTest extends RestResourceConfigResourceTestBase {

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
