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
  public static $modules = ['theme_test'];

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
  protected function setUp() {
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

    $base = drupal_get_path('theme', 'test_basetheme');
    $sub = drupal_get_path('theme', 'test_subtheme') . '/css';

    // All removals are expected to be based on a file's path and name and
    // should work nevertheless.
    $this->drupalGet('theme-test/info/stylesheets');

    $this->assertCount(1, $this->xpath('//link[contains(@href, :href)]', [':href' => "$base/base-add.css"]), "$base/base-add.css found");
    $this->assertCount(0, $this->xpath('//link[contains(@href, :href)]', [':href' => "base-remove.css"]), "base-remove.css not found");

    $this->assertCount(1, $this->xpath('//link[contains(@href, :href)]', [':href' => "$sub/sub-add.css"]), "$sub/sub-add.css found");
    $this->assertCount(0, $this->xpath('//link[contains(@href, :href)]', [':href' => "sub-remove.css"]), "sub-remove.css not found");
    $this->assertCount(0, $this->xpath('//link[contains(@href, :href)]', [':href' => "base-add.sub-remove.css"]), "base-add.sub-remove.css not found");

    // Verify that CSS files with the same name are loaded from both the base theme and subtheme.
    $this->assertCount(1, $this->xpath('//link[contains(@href, :href)]', [':href' => "$base/samename.css"]), "$base/samename.css found");
    $this->assertCount(1, $this->xpath('//link[contains(@href, :href)]', [':href' => "$sub/samename.css"]), "$sub/samename.css found");

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
    $this->assertEqual('test_theme', $active_theme->getName());
    $this->assertEqual(['classy/base', 'classy/messages', 'core/normalize', 'test_theme/global-styling'], $active_theme->getLibraries());

    // @see theme_test_system_info_alter()
    $this->state->set('theme_test.modify_info_files', TRUE);
    drupal_flush_all_caches();
    $active_theme = $this->themeManager->getActiveTheme();
    $this->assertEqual(['classy/base', 'classy/messages', 'core/normalize', 'test_theme/global-styling', 'core/backbone'], $active_theme->getLibraries());
  }

}
