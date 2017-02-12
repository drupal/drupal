<?php

namespace Drupal\Tests\hal\Functional\EntityResource\MenuLinkContent;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
class MenuLinkContentHalJsonBasicAuthTest extends MenuLinkContentHalJsonAnonTest {

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
