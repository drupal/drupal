<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityTestBundle;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityTestBundle\EntityTestBundleResourceTestBase;

/**
 * @group hal
 */
class EntityTestBundleHalJsonCookieTest extends EntityTestBundleResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

}
