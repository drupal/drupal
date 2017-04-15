<?php

namespace Drupal\Tests\hal\Functional\EntityResource\FieldConfig;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\FieldConfig\FieldConfigResourceTestBase;

/**
 * @group hal
 */
class FieldConfigHalJsonAnonTest extends FieldConfigResourceTestBase {

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
