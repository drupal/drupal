<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests processing of theme .info.yml properties.
 *
 * @group Theme
 */
class ThemeInfoTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['theme_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The theme installer used in this test for enabling themes.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * The theme manager used in this test.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The state service used in this test.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->themeInstaller = $this->container->get('theme_installer');
    $this->themeManager = $this->container->get('theme.manager');
    $this->state = $this->container->get('state');
  }

  /**
   * Tests stylesheets-remove.
   */
  public function testStylesheets() {
    $this->themeInstaller->install(['test_basetheme', 'test_subtheme']);
    $this->config('system.theme')
      ->set('default', 'test_subtheme')
      ->save();

    $base = $this->getThemePath('test_basetheme');
    $sub = $this->getThemePath('test_subtheme') . '/css';

    // All removals are expected to be based on a file's path and name and
    // should work nevertheless.
    $this->drupalGet('theme-test/info/stylesheets');

    $this->assertSession()->elementsCount('xpath', '//link[contains(@href, "' . $base . '/base-add.css")]', 1);
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "base-remove.css")]');

    $this->assertSession()->elementsCount('xpath', '//link[contains(@href, "' . $sub . '/sub-add.css")]', 1);
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "sub-remove.css")]');
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "base-add.sub-remove.css")]');

    // Verify that CSS files with the same name are loaded from both the base theme and subtheme.
    $this->assertSession()->elementsCount('xpath', '//link[contains(@href, "' . $base . '/samename.css")]', 1);
    $this->assertSession()->elementsCount('xpath', '//link[contains(@href, "' . $sub . '/samename.css")]', 1);

  }

  /**
   * Tests that changes to the info file are picked up.
   */
  public function testChanges() {
    $this->themeInstaller->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();
    $this->themeManager->resetActiveTheme();

    $active_theme = $this->themeManager->getActiveTheme();
    // Make sure we are not testing the wrong theme.
    $this->assertEquals('test_theme', $active_theme->getName());
    $this->assertEquals(['classy/base', 'classy/messages', 'core/normalize', 'test_theme/global-styling'], $active_theme->getLibraries());

    // @see theme_test_system_info_alter()
    $this->state->set('theme_test.modify_info_files', TRUE);
    $this->resetAll();
    $active_theme = $this->themeManager->getActiveTheme();
    $this->assertEquals(['classy/base', 'classy/messages', 'core/normalize', 'test_theme/global-styling', 'core/once'], $active_theme->getLibraries());
  }

}
