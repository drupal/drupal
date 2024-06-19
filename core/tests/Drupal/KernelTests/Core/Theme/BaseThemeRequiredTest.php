<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior of the `base theme` key.
 *
 * @group Theme
 */
class BaseThemeRequiredTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * The theme installer.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->themeInstaller = $this->container->get('theme_installer');
    $this->themeManager = $this->container->get('theme.manager');
  }

  /**
   * Tests opting out of Stable 9 by setting the base theme to false.
   */
  public function testWildWest(): void {
    $this->themeInstaller->install(['test_wild_west']);
    $this->config('system.theme')->set('default', 'test_wild_west')->save();
    $theme = $this->themeManager->getActiveTheme();
    /** @var \Drupal\Core\Theme\ActiveTheme $base_theme */
    $base_themes = $theme->getBaseThemeExtensions();
    $this->assertEmpty($base_themes, 'No base theme is set when a theme has opted out of using Stable 9.');
  }

}
