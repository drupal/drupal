<?php

namespace Drupal\Tests\views\Functional\Hal;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\views\Functional\Rest\ViewResourceTestBase;

/**
 * @group hal
 */
class ViewHalJsonAnonTest extends ViewResourceTestBase {

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
