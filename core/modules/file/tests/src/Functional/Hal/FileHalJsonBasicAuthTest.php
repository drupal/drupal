<?php

namespace Drupal\Tests\file\Functional\Hal;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
class FileHalJsonBasicAuthTest extends FileHalJsonAnonTest {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

}
