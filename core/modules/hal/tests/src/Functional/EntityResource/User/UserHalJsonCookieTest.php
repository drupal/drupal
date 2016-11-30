<?php

namespace Drupal\Tests\hal\Functional\EntityResource\User;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class UserHalJsonCookieTest extends UserHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
