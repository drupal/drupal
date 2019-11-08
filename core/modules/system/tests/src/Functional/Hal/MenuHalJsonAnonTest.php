<?php

namespace Drupal\Tests\system\Functional\Hal;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\system\Functional\Rest\MenuResourceTestBase;

/**
 * @group hal
 */
class MenuHalJsonAnonTest extends MenuResourceTestBase {

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
