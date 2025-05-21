<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

/**
 * Tests discovery of components in a theme being installed or uninstalled.
 *
 * @group sdc
 */
class ComponentPluginManagerCachedDiscoveryTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['stark'];

  /**
   * Tests cached component plugin discovery on theme install and uninstall.
   */
  public function testComponentDiscoveryOnThemeInstall(): void {
    // Component in sdc_theme should not be found without sdc_theme installed.
    $definitions = \Drupal::service('plugin.manager.sdc')->getDefinitions();
    $this->assertArrayNotHasKey('sdc_theme_test:bar', $definitions);

    // Component in sdc_theme should be found once sdc_theme is installed.
    \Drupal::service('theme_installer')->install(['sdc_theme_test']);
    $definitions = \Drupal::service('plugin.manager.sdc')->getDefinitions();
    $this->assertArrayHasKey('sdc_theme_test:bar', $definitions);

    // Component in sdc_theme should not be found once sdc_theme is uninstalled.
    \Drupal::service('theme_installer')->uninstall(['sdc_theme_test']);
    $definitions = \Drupal::service('plugin.manager.sdc')->getDefinitions();
    $this->assertArrayNotHasKey('sdc_theme_test:bar', $definitions);
  }

}
