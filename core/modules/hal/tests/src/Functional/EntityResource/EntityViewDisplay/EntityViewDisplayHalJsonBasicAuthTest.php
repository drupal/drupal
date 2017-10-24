<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityViewDisplay;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
class EntityViewDisplayHalJsonBasicAuthTest extends EntityViewDisplayHalJsonAnonTest {

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
