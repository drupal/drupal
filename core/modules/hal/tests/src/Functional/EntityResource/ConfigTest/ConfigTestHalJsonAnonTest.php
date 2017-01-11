<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ConfigTest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ConfigTest\ConfigTestResourceTestBase;

/**
 * @group hal
 */
class ConfigTestHalJsonAnonTest extends ConfigTestResourceTestBase {

  use AnonResourceTestTrait;

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

}
