<?php

namespace Drupal\Tests\hal\Functional\EntityResource\BlockContent;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class BlockContentHalJsonCookieTest extends BlockContentHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
