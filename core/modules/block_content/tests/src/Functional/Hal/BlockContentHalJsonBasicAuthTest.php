<?php

namespace Drupal\Tests\block_content\Functional\Hal;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
class BlockContentHalJsonBasicAuthTest extends BlockContentHalJsonAnonTest {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

}
