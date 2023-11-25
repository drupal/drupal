<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Extension\InfoParserException;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior of a theme when base_theme info key is missing.
 *
 * @group Theme
 */
class BaseThemeMissingTest extends KernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add a directory to extension discovery to find the theme with a missing
    // base class.
    // @see \Drupal\Core\Extension\ExtensionDiscovery::scan()
    $settings = Settings::getAll();
    $settings['test_parent_site'] = 'core/tests/fixtures/test_missing_base_theme';
    new Settings($settings);

    $this->themeInstaller = $this->container->get('theme_installer');
  }

  /**
   * Tests exception is thrown.
   */
  public function testMissingBaseThemeException() {
    $this->expectException(InfoParserException::class);
    $this->expectExceptionMessage('Missing required key ("base theme") in core/tests/fixtures/test_missing_base_theme/test_missing_base_theme.info.yml, see https://www.drupal.org/node/3066038');
    $this->themeInstaller->install(['test_missing_base_theme']);
  }

}
