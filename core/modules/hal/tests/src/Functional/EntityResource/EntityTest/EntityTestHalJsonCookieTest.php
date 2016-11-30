<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityTest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class EntityTestHalJsonCookieTest extends EntityTestHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
