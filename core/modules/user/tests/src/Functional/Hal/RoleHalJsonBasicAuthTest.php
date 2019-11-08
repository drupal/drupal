<?php

namespace Drupal\Tests\user\Functional\Hal;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\user\Functional\Rest\RoleResourceTestBase;

/**
 * @group hal
 */
class RoleHalJsonBasicAuthTest extends RoleResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal', 'basic_auth'];

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
  protected static $auth = 'basic_auth';

}
