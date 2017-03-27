<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityTestLabel;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class EntityTestLabelHalJsonCookieTest extends EntityTestLabelHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
