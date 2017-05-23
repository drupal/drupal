<?php

namespace Drupal\Tests\hal\Functional\EntityResource\BaseFieldOverride;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\BaseFieldOverride\BaseFieldOverrideResourceTestBase;

/**
 * @group hal
 */
class BaseFieldOverrideHalJsonAnonTest extends BaseFieldOverrideResourceTestBase {

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
