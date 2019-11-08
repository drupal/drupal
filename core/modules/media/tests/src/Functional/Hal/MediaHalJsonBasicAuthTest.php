<?php

namespace Drupal\Tests\media\Functional\Hal;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
class MediaHalJsonBasicAuthTest extends MediaHalJsonAnonTest {

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
