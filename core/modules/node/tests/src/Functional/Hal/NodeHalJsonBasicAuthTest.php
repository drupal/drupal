<?php

namespace Drupal\Tests\node\Functional\Hal;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
class NodeHalJsonBasicAuthTest extends NodeHalJsonAnonTest {

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
