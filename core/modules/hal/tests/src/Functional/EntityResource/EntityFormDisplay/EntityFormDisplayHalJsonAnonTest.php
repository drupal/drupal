<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityFormDisplay;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityFormDisplay\EntityFormDisplayResourceTestBase;

/**
 * @group hal
 */
class EntityFormDisplayHalJsonAnonTest extends EntityFormDisplayResourceTestBase {

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
