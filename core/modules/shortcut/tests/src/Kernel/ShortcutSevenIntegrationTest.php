<?php

namespace Drupal\Tests\shortcut\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests shortcut_install() and shortcut_uninstall().
 *
 * @group shortcut
 */
class ShortcutSevenIntegrationTest extends KernelTestBase {

  protected static $modules = ['system'];

  /**
   * Tests shortcut_install() and shortcut_uninstall().
   */
  public function testInstallUninstall() {
    // Install seven.
    \Drupal::service('theme_installer')->install(['seven']);
    $this->assertNull($this->config('seven.settings')->get('third_party_settings.shortcut'), 'There are no shortcut settings in seven.settings.');

    \Drupal::service('module_installer')->install(['shortcut']);
    $this->assertTrue($this->config('seven.settings')->get('third_party_settings.shortcut.module_link'), 'The shortcut module_link setting is in seven.settings.');

    \Drupal::service('module_installer')->uninstall(['shortcut']);
    $this->assertNull($this->config('seven.settings')->get('third_party_settings.shortcut'), 'There are no shortcut settings in seven.settings.');
  }

}
