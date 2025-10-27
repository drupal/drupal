<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests theme settings functionality.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class ThemeSettingsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * List of discovered themes.
   *
   * @var array
   */
  protected $availableThemes;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
  public function testDefaultConfig(): void {
    $name = 'test_base_theme';
    $path = $this->availableThemes[$name]->getPath();
    $this->assertFileExists("$path/" . InstallStorage::CONFIG_INSTALL_DIRECTORY . "/$name.settings.yml");
    $this->container->get('theme_installer')->install([$name]);
    $this->assertSame('only', \Drupal::service(ThemeSettingsProvider::class)->getSetting('base', $name));
  }

  /**
   * Tests that the $theme.settings default config file is optional.
   */
  public function testNoDefaultConfig(): void {
    $name = 'stark';
    $path = $this->availableThemes[$name]->getPath();
    $this->assertFileDoesNotExist("$path/" . InstallStorage::CONFIG_INSTALL_DIRECTORY . "/$name.settings.yml");
    $this->container->get('theme_installer')->install([$name]);
    $this->assertNotNull(\Drupal::service(ThemeSettingsProvider::class)->getSetting('features.favicon', $name));
  }

  /**
   * Tests that the default logo config can be overridden.
   */
  public function testLogoConfig(): void {
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install(['stark']);
    /** @var \Drupal\Core\Extension\ThemeHandler $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme = $theme_handler->getTheme('stark');
    // Tests default behavior.
    $expected = '/' . $theme->getPath() . '/logo.svg';
    $this->assertEquals($expected, \Drupal::service(ThemeSettingsProvider::class)->getSetting('logo.url', 'stark'));

    $config = $this->config('stark.settings');
    \Drupal::service('cache.memory')->invalidateTags(['config:stark.settings']);

    $values = [
      'default_logo' => FALSE,
      'logo_path' => 'public://logo_with_scheme.png',
    ];
    theme_settings_convert_to_config($values, $config)->save();

    // Tests logo path with scheme.
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $expected = $file_url_generator->generateString('public://logo_with_scheme.png');
    $this->assertEquals($expected, \Drupal::service(ThemeSettingsProvider::class)->getSetting('logo.url', 'stark'));

    $values = [
      'default_logo' => FALSE,
      'logo_path' => $theme->getPath() . '/logo_relative_path.gif',
    ];
    theme_settings_convert_to_config($values, $config)->save();

    \Drupal::service('cache.memory')->invalidateTags(['config:stark.settings']);

    // Tests relative path.
    $expected = '/' . $theme->getPath() . '/logo_relative_path.gif';
    $this->assertEquals($expected, \Drupal::service(ThemeSettingsProvider::class)->getSetting('logo.url', 'stark'));

    $theme_installer->install(['test_theme']);
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $theme_handler = $this->container->get('theme_handler');
    $theme = $theme_handler->getTheme('test_theme');

    // Tests logo set in test_theme.info.yml.
    $expected = '/' . $theme->getPath() . '/images/logo2.svg';
    $this->assertEquals($expected, \Drupal::service(ThemeSettingsProvider::class)->getSetting('logo.url', 'test_theme'));
  }

}
