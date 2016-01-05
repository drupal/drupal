<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\StableThemeTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the behavior of the Stable theme.
 *
 * @group Theme
 */
class StableThemeTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->themeHandler = $this->container->get('theme_handler');
    $this->themeManager = $this->container->get('theme.manager');
  }

  /**
   * Ensures Stable is used by default when no base theme has been defined.
   */
  public function testStableIsDefault() {
    $this->themeHandler->install(['test_stable']);
    $this->config('system.theme')->set('default', 'test_stable')->save();
    $theme = $this->themeManager->getActiveTheme();
    /** @var \Drupal\Core\Theme\ActiveTheme $base_theme */
    $base_themes = $theme->getBaseThemes();
    $base_theme = reset($base_themes);
    $this->assertTrue($base_theme->getName() == 'stable', "Stable theme is the base theme if a theme hasn't decided to opt out.");
  }

  /**
   * Tests opting out of Stable by setting the base theme to false.
   */
  public function testWildWest() {
    $this->themeHandler->install(['test_wild_west']);
    $this->config('system.theme')->set('default', 'test_wild_west')->save();
    $theme = $this->themeManager->getActiveTheme();
    /** @var \Drupal\Core\Theme\ActiveTheme $base_theme */
    $base_themes = $theme->getBaseThemes();
    $this->assertTrue(empty($base_themes), 'No base theme is set when a theme has opted out of using Stable.');
  }

}
