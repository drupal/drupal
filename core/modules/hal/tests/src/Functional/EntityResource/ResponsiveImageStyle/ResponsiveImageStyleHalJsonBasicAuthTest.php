<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ResponsiveImageStyle;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ResponsiveImageStyle\ResponsiveImageStyleResourceTestBase;

/**
 * @group hal
 */
class ResponsiveImageStyleHalJsonBasicAuthTest extends ResponsiveImageStyleResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal', 'basic_auth'];

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
