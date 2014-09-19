<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\ThemeSettingsTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests theme settings functionality.
 *
 * @group Theme
 */
class ThemeSettingsTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * List of discovered themes.
   *
   * @var array
   */
  protected $availableThemes;

  protected function setUp() {
    parent::setUp();
    // Theme settings rely on System module's system.theme.global configuration.
    $this->installConfig(array('system'));

    if (!isset($this->availableThemes)) {
      $discovery = new ExtensionDiscovery();
      $this->availableThemes = $discovery->scan('theme');
    }
  }

  /**
   * Tests that $theme.settings are imported and used as default theme settings.
   */
  function testDefaultConfig() {
    $name = 'test_basetheme';
    $path = $this->availableThemes[$name]->getPath();
    $this->assertTrue(file_exists("$path/" . InstallStorage::CONFIG_INSTALL_DIRECTORY . "/$name.settings.yml"));
    $this->container->get('theme_handler')->install(array($name));
    $this->assertIdentical(theme_get_setting('base', $name), 'only');
  }

  /**
   * Tests that the $theme.settings default config file is optional.
   */
  function testNoDefaultConfig() {
    $name = 'stark';
    $path = $this->availableThemes[$name]->getPath();
    $this->assertFalse(file_exists("$path/" . InstallStorage::CONFIG_INSTALL_DIRECTORY . "/$name.settings.yml"));
    $this->container->get('theme_handler')->install(array($name));
    $this->assertNotNull(theme_get_setting('features.favicon', $name));
  }

}
