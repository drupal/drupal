<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityViewDisplay;

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
