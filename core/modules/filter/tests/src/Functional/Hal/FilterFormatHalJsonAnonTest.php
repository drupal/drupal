<?php

namespace Drupal\Tests\filter\Functional\Hal;

use Drupal\Tests\filter\Functional\Rest\FilterFormatResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class FilterFormatHalJsonAnonTest extends FilterFormatResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal'];

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
