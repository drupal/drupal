<?php

namespace Drupal\Tests\aggregator\Functional\Hal;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class ItemHalJsonAnonTest extends ItemHalJsonTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

}
