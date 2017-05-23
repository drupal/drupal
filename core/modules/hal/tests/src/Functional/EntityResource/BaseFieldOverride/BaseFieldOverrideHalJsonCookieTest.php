<?php

namespace Drupal\Tests\hal\Functional\EntityResource\BaseFieldOverride;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\BaseFieldOverride\BaseFieldOverrideResourceTestBase;

/**
 * @group hal
 */
class BaseFieldOverrideHalJsonCookieTest extends BaseFieldOverrideResourceTestBase {

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
