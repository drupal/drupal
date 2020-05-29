<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests theme settings functionality.
 *
 * @group Theme
 */
class ThemeSettingsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * List of discovered themes.
   *
   * @var array
   */
  protected $availableThemes;

  protected function setUp() {
    parent::setUp();
    // Theme settings rely on System module's system.theme.global configuration.
    $this->installConfig(['system']);

    if (!isset($this->availableThemes)) {
      $discovery = new ExtensionDiscovery($this->root);
      $this->availableThemes = $discovery->scan('theme');
    }
  }

  /**
   * Tests that $theme.settings are imported and used as default theme settings.
   */
  public function testDefaultConfig() {
    $name = 'test_basetheme';
    $path = $this->availableThemes[$name]->getPath();
    $this->assertFileExists("$path/" . InstallStorage::CONFIG_INSTALL_DIRECTORY . "/$name.settings.yml");
    $this->container->get('theme_installer')->install([$name]);
    $this->assertIdentical(theme_get_setting('base', $name), 'only');
  }

  /**
   * Tests that the $theme.settings default config file is optional.
   */
  public function testNoDefaultConfig() {
    $name = 'stark';
    $path = $this->availableThemes[$name]->getPath();
    $this->assertFileNotExists("$path/" . InstallStorage::CONFIG_INSTALL_DIRECTORY . "/$name.settings.yml");
    $this->container->get('theme_installer')->install([$name]);
    $this->assertNotNull(theme_get_setting('features.favicon', $name));
  }

  /**
   * Tests that the default logo config can be overridden.
   */
  public function testLogoConfig() {
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install(['stark']);
    /** @var \Drupal\Core\Extension\ThemeHandler $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme = $theme_handler->getTheme('stark');

    // Tests default behavior.
    $expected = '/' . $theme->getPath() . '/logo.svg';
    $this->assertEquals($expected, theme_get_setting('logo.url', 'stark'));

    $config = $this->config('stark.settings');
    drupal_static_reset('theme_get_setting');

    $values = [
      'default_logo' => FALSE,
      'logo_path' => 'public://logo_with_scheme.png',
    ];
    theme_settings_convert_to_config($values, $config)->save();

    // Tests logo path with scheme.
    $expected = file_url_transform_relative(file_create_url('public://logo_with_scheme.png'));
    $this->assertEquals($expected, theme_get_setting('logo.url', 'stark'));

    $values = [
      'default_logo' => FALSE,
      'logo_path' => $theme->getPath() . '/logo_relative_path.gif',
    ];
    theme_settings_convert_to_config($values, $config)->save();

    drupal_static_reset('theme_get_setting');

    // Tests relative path.
    $expected = '/' . $theme->getPath() . '/logo_relative_path.gif';
    $this->assertEquals($expected, theme_get_setting('logo.url', 'stark'));

    $theme_installer->install(['test_theme']);
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $theme = $theme_handler->getTheme('test_theme');

    drupal_static_reset('theme_get_setting');

    // Tests logo set in test_theme.info.yml.
    $expected = '/' . $theme->getPath() . '/images/logo2.svg';
    $this->assertEquals($expected, theme_get_setting('logo.url', 'test_theme'));
  }

}
