<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityFormDisplay;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityFormDisplay\EntityFormDisplayResourceTestBase;

/**
 * @group hal
 */
class EntityFormDisplayHalJsonCookieTest extends EntityFormDisplayResourceTestBase {

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
