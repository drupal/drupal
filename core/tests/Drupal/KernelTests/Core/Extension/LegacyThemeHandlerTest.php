<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated theme handler methods.
 *
 * @group Extension
 * @group legacy
 */
class LegacyThemeHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * Tests that a deprecation error is thrown when calling ::setDefault.
   *
   * @expectedDeprecation Drupal\Core\Extension\ThemeHandler::setDefault is deprecated in drupal:8.2.0 and is removed from drupal:9.0.0. Use the configuration system to edit the system.theme config directly. See https://www.drupal.org/node/3082630
   */
  public function testSetDefault() {
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->install(['bartik']);
    \Drupal::service('theme_handler')->setDefault('bartik');
    $this->assertSame('bartik', \Drupal::config('system.theme')->get('default'));
  }

}
