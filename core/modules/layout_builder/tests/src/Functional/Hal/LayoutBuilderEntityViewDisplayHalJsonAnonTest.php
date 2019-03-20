<?php

namespace Drupal\Tests\layout_builder\Functional\Hal;

use Drupal\Tests\layout_builder\Functional\Rest\LayoutBuilderEntityViewDisplayResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group layout_builder
 * @group rest
 */
class LayoutBuilderEntityViewDisplayHalJsonAnonTest extends LayoutBuilderEntityViewDisplayResourceTestBase {

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
