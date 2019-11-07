<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;

/**
 * Tests the behavior of the Stable theme.
 *
 * @group Theme
 */
class BaseThemeDefaultDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

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
  protected function setUp() {
    parent::setUp();

    $this->themeInstaller = $this->container->get('theme_installer');
    $this->themeManager = $this->container->get('theme.manager');
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
        'test_stable' => [
          'test_stable.info.yml' => file_get_contents(DRUPAL_ROOT . '/core/tests/fixtures/test_stable/test_stable.info.yml'),
          'test_stable.theme' => file_get_contents(DRUPAL_ROOT . '/core/tests/fixtures/test_stable/test_stable.theme'),
        ],
        'stable' => [
          'stable.info.yml' => file_get_contents(DRUPAL_ROOT . '/core/themes/stable/stable.info.yml'),
          'stable.theme' => file_get_contents(DRUPAL_ROOT . '/core/themes/stable/stable.theme'),
        ],
      ],
    ], $vfs_root);
  }

  /**
   * Ensures Stable is used by default when no base theme has been defined.
   *
   * @group legacy
   * @expectedDeprecation There is no `base theme` property specified in the test_stable.info.yml file. The optionality of the `base theme` property is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. All Drupal 8 themes must add `base theme: stable` to their *.info.yml file for them to continue to work as-is in future versions of Drupal. Drupal 9 requires the `base theme` property to be specified. See https://www.drupal.org/node/3066038
   */
  public function testStableIsDefault() {
    $this->container->get('extension.list.theme')
      ->setExtensionDiscovery(new ExtensionDiscovery('vfs://core'))
      ->setInfoParser(new VfsInfoParser('vfs:/'));

    $this->themeInstaller->install(['test_stable']);
    $this->config('system.theme')->set('default', 'test_stable')->save();
    $theme = $this->themeManager->getActiveTheme();
    $base_themes = $theme->getBaseThemeExtensions();
    $base_theme = reset($base_themes);
    $this->assertTrue($base_theme->getName() == 'stable', "Stable theme is the base theme if a theme hasn't decided to opt out.");
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
   * Sets the info parser.
   *
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser.
   *
   * @return self
   */
  public function setInfoParser(InfoParserInterface $info_parser) {
    $this->infoParser = $info_parser;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionDiscovery() {
    return $this->extensionDiscovery;
  }

}

class VfsInfoParser extends InfoParser {

  /**
   * {@inheritdoc}
   */
  public function parse($filename) {
    return parent::parse("vfs://core/$filename");
  }

}
