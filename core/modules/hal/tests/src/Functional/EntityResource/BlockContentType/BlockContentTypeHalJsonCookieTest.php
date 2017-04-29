<?php

namespace Drupal\Tests\hal\Functional\EntityResource\BlockContentType;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\BlockContentType\BlockContentTypeResourceTestBase;

/**
 * @group hal
 */
class BlockContentTypeHalJsonCookieTest extends BlockContentTypeResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
