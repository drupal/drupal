<?php

namespace Drupal\Tests\hal\Functional\EntityResource\FieldStorageConfig;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\FieldStorageConfig\FieldStorageConfigResourceTestBase;

/**
 * @group hal
 */
class FieldStorageConfigHalJsonAnonTest extends FieldStorageConfigResourceTestBase {

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
