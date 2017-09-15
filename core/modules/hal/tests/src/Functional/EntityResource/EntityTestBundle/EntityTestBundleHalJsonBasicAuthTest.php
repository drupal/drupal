<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityTestBundle;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityTestBundle\EntityTestBundleResourceTestBase;

/**
 * @group hal
 */
class EntityTestBundleHalJsonBasicAuthTest extends EntityTestBundleResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal', 'basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

}
