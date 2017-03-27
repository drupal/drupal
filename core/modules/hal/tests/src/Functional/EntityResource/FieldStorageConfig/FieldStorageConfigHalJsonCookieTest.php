<?php

namespace Drupal\Tests\hal\Functional\EntityResource\FieldStorageConfig;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\FieldStorageConfig\FieldStorageConfigResourceTestBase;

/**
 * @group hal
 */
class FieldStorageConfigHalJsonCookieTest extends FieldStorageConfigResourceTestBase {

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
