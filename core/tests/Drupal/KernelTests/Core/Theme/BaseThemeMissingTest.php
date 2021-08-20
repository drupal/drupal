<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParserException;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;

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

    $this->themeInstaller = $this->container->get('theme_installer');
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $container->getDefinition('extension.list.theme')
      ->setClass(VfsThemeExtensionList::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem() {
    parent::setUpFilesystem();

    $vfs_root = vfsStream::setup('core');
    vfsStream::create([
      'themes' => [
        'test_missing_base_theme' => [
          'test_missing_base_theme.info.yml' => file_get_contents(DRUPAL_ROOT . '/core/tests/fixtures/test_missing_base_theme/test_missing_base_theme.info.yml'),
          'test_missing_base_theme.theme' => file_get_contents(DRUPAL_ROOT . '/core/tests/fixtures/test_missing_base_theme/test_missing_base_theme.theme'),
        ],
      ],
    ], $vfs_root);
  }

  /**
   * Tests exception is thrown.
   */
  public function testMissingBaseThemeException() {
    $this->container->get('extension.list.theme')
      ->setExtensionDiscovery(new ExtensionDiscovery('vfs://core'));

    $this->expectException(InfoParserException::class);
    $this->expectExceptionMessage('Missing required key ("base theme") in themes/test_missing_base_theme/test_missing_base_theme.info.yml, see https://www.drupal.org/node/3066038');
    $this->themeInstaller->install(['test_missing_base_theme']);
  }

}

/**
 * Test theme extension list class.
 */
class VfsThemeExtensionList extends ThemeExtensionList {

  /**
   * The extension discovery for this extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected $extensionDiscovery;

  /**
   * Sets the extension discovery.
   *
   * @param \Drupal\Core\Extension\ExtensionDiscovery $discovery
   *   The extension discovery.
   *
   * @return self
   */
  public function setExtensionDiscovery(ExtensionDiscovery $discovery) {
    $this->extensionDiscovery = $discovery;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionDiscovery() {
    return $this->extensionDiscovery;
  }

}
