<?php

namespace Drupal\Tests\hal\Functional\EntityResource\File;

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
  protected static $auth = 'basic_auth';

}
