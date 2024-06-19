<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests shortcut_install() and shortcut_uninstall().
 *
 * @group shortcut
 */
class ShortcutClaroIntegrationTest extends KernelTestBase {

  protected static $modules = ['system'];

  /**
   * Tests shortcut_install() and shortcut_uninstall().
   */
  public function testInstallUninstall(): void {
    // Install claro.
    \Drupal::service('theme_installer')->install(['claro']);
    $this->assertNull($this->config('claro.settings')->get('third_party_settings.shortcut'), 'There are no shortcut settings in claro.settings.');

    \Drupal::service('module_installer')->install(['shortcut']);
    $this->assertTrue($this->config('claro.settings')->get('third_party_settings.shortcut.module_link'), 'The shortcut module_link setting is in claro.settings.');

    \Drupal::service('module_installer')->uninstall(['shortcut']);
    $this->assertNull($this->config('claro.settings')->get('third_party_settings.shortcut'), 'There are no shortcut settings in claro.settings.');
  }

}
