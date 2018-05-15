<?php

namespace Drupal\Tests\entity_test\Functional\Hal;

use Drupal\Tests\entity_test\Functional\Rest\EntityTestBundleResourceTestBase;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class EntityTestBundleHalJsonAnonTest extends EntityTestBundleResourceTestBase {

  use HalEntityNormalizationTrait;
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
