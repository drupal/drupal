<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ResponsiveImageStyle;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ResponsiveImageStyle\ResponsiveImageStyleResourceTestBase;

/**
 * @group hal
 */
class ResponsiveImageStyleHalJsonAnonTest extends ResponsiveImageStyleResourceTestBase {

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
