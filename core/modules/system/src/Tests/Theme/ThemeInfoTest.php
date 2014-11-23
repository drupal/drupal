<?php

/**
 * @file
 * Contains Drupal\system\Tests\Theme\ThemeInfoTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests processing of theme .info.yml properties.
 *
 * @group Theme
 */
class ThemeInfoTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  /**
   * The theme handler used in this test for enabling themes.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

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

    $this->themeHandler = $this->container->get('theme_handler');
    $this->themeManager = $this->container->get('theme.manager');
    $this->state = $this->container->get('state');
  }

  /**
   * Tests stylesheets-override and stylesheets-remove.
   */
  function testStylesheets() {
    $this->themeHandler->install(array('test_basetheme', 'test_subtheme'));
    \Drupal::config('system.theme')
      ->set('default', 'test_subtheme')
      ->save();

    $base = drupal_get_path('theme', 'test_basetheme');
    // Unlike test_basetheme (and the original module CSS), the subtheme decides
    // to put all of its CSS into a ./css subdirectory. All overrides and
    // removals are expected to be based on a file's basename and should work
    // nevertheless.
    $sub = drupal_get_path('theme', 'test_subtheme') . '/css';

    $this->drupalGet('theme-test/info/stylesheets');

    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$base/base-add.css')]")), "$base/base-add.css found");
    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$base/base-override.css')]")), "$base/base-override.css found");
    $this->assertIdentical(0, count($this->xpath("//link[contains(@href, 'base-remove.css')]")), "base-remove.css not found");

    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$sub/sub-add.css')]")), "$sub/sub-add.css found");

    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$sub/sub-override.css')]")), "$sub/sub-override.css found");
    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$sub/base-add.sub-override.css')]")), "$sub/base-add.sub-override.css found");
    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$sub/base-remove.sub-override.css')]")), "$sub/base-remove.sub-override.css found");

    $this->assertIdentical(0, count($this->xpath("//link[contains(@href, 'sub-remove.css')]")), "sub-remove.css not found");
    $this->assertIdentical(0, count($this->xpath("//link[contains(@href, 'base-add.sub-remove.css')]")), "base-add.sub-remove.css not found");
    $this->assertIdentical(0, count($this->xpath("//link[contains(@href, 'base-override.sub-remove.css')]")), "base-override.sub-remove.css not found");
  }

  /**
   * Tests that changes to the info file are picked up.
   */
  public function testChanges() {
    $this->themeHandler->install(array('test_theme'));
    $this->themeHandler->setDefault('test_theme');
    $this->themeManager->resetActiveTheme();

    $active_theme = $this->themeManager->getActiveTheme();
    // Make sure we are not testing the wrong theme.
    $this->assertEqual('test_theme', $active_theme->getName());
    $this->assertEqual(['classy/base'], $active_theme->getLibraries());

    // @see theme_test_system_info_alter()
    $this->state->set('theme_test.modify_info_files', TRUE);
    drupal_flush_all_caches();
    $active_theme = $this->themeManager->getActiveTheme();
    $this->assertEqual(['classy/base', 'core/backbone'], $active_theme->getLibraries());
  }

}
