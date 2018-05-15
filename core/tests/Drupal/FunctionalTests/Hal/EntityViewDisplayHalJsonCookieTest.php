<?php

namespace Drupal\FunctionalTests\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class EntityViewDisplayHalJsonCookieTest extends EntityViewDisplayHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
