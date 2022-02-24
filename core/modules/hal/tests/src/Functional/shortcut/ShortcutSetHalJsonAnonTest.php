<?php

namespace Drupal\Tests\hal\Functional\shortcut;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\shortcut\Functional\Rest\ShortcutSetResourceTestBase;

/**
 * @group hal
 * @group legacy
 */
class ShortcutSetHalJsonAnonTest extends ShortcutSetResourceTestBase {

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
