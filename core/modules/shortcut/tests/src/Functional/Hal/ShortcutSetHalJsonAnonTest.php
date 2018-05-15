<?php

namespace Drupal\Tests\shortcut\Functional\Hal;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\shortcut\Functional\Rest\ShortcutSetResourceTestBase;

/**
 * @group hal
 */
class ShortcutSetHalJsonAnonTest extends ShortcutSetResourceTestBase {

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
