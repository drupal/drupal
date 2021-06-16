<?php

namespace Drupal\Tests\field\Functional\Hal;

use Drupal\Tests\field\Functional\Rest\FieldConfigResourceTestBase;
use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
class FieldConfigHalJsonBasicAuthTest extends FieldConfigResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal', 'basic_auth'];

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
