<?php

namespace Drupal\Tests\hal\Functional\EntityResource\FilterFormat;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\FilterFormat\FilterFormatResourceTestBase;

/**
 * @group hal
 */
class FilterFormatHalJsonAnonTest extends FilterFormatResourceTestBase {

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
