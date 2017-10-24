<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityViewDisplay;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityViewDisplay\EntityViewDisplayResourceTestBase;

/**
 * @group hal
 */
class EntityViewDisplayHalJsonAnonTest extends EntityViewDisplayResourceTestBase {

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
