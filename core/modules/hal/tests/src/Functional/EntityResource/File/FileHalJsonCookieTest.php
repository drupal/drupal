<?php

namespace Drupal\Tests\hal\Functional\EntityResource\File;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class FileHalJsonCookieTest extends FileHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
