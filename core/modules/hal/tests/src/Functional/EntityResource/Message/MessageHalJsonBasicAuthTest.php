<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Message;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
class MessageHalJsonBasicAuthTest extends MessageHalJsonAnonTest {

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
