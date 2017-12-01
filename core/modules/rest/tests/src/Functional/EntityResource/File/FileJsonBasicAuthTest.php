<?php

namespace Drupal\Tests\rest\Functional\EntityResource\File;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group rest
 */
class FileJsonBasicAuthTest extends FileResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

}
