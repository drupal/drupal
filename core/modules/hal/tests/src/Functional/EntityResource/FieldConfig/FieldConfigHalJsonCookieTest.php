<?php

namespace Drupal\Tests\hal\Functional\EntityResource\FieldConfig;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\FieldConfig\FieldConfigResourceTestBase;

/**
 * @group hal
 */
class FieldConfigHalJsonCookieTest extends FieldConfigResourceTestBase {

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
