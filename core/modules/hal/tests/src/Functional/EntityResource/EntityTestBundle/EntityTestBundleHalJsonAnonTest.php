<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityTestBundle;

use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityTestBundle\EntityTestBundleResourceTestBase;

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
