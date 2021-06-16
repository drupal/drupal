<?php

namespace Drupal\Tests\editor\Functional\Hal;

use Drupal\Tests\editor\Functional\Rest\EditorResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class EditorHalJsonAnonTest extends EditorResourceTestBase {

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
