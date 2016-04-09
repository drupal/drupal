<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests themes and base themes are correctly loaded.
 *
 * @group Installer
 */
class MaintenanceThemeTest extends KernelTestBase {

  /**
   * Tests that the maintenance theme initializes the theme and its base themes.
   */
  public function testMaintenanceTheme() {
    $this->setSetting('maintenance_theme', 'seven');
    // Get the maintenance theme loaded.
    drupal_maintenance_theme();

    // Do we have an active theme?
    $this->assertTrue(\Drupal::theme()->hasActiveTheme());

    $active_theme = \Drupal::theme()->getActiveTheme();
    $this->assertEquals('seven', $active_theme->getName());

    $base_themes = $active_theme->getBaseThemes();
    $base_theme_names = array_keys($base_themes);
    $this->assertSame(['classy', 'stable'], $base_theme_names);

    // Ensure Classy has the correct base themes and amount of base themes.
    $classy_base_themes = $base_themes['classy']->getBaseThemes();
    $classy_base_theme_names = array_keys($classy_base_themes);
    $this->assertSame(['stable'], $classy_base_theme_names);
  }

}
