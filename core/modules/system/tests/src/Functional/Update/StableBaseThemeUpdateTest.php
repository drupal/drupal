<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeEngineExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\State\StateInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use org\bovigo\vfs\vfsStream;

/**
 * Tests the upgrade path for introducing the Stable base theme.
 *
 * @see https://www.drupal.org/node/2575421
 *
 * @group system
 * @group legacy
 */
class StableBaseThemeUpdateTest extends UpdatePathTestBase implements ServiceProviderInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.stable-base-theme-2575421.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->getDefinition('extension.list.theme')
      ->setClass(VfsThemeExtensionList::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $GLOBALS['conf']['container_service_providers']['test'] = $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->themeHandler = $this->container->get('theme_handler');
    $this->themeHandler->refreshInfo();

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
   * Tests that the Stable base theme is installed if necessary.
   *
   * @expectedDeprecation There is no `base theme` property specified in the test_stable.info.yml file. The optionality of the `base theme` property is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. All Drupal 8 themes must add `base theme: stable` to their *.info.yml file for them to continue to work as-is in future versions of Drupal. Drupal 9 requires the `base theme` property to be specified. See https://www.drupal.org/node/3066038
   */
  public function testUpdateHookN() {
    $this->assertTrue($this->themeHandler->themeExists('test_stable'));
    $this->assertFalse($this->themeHandler->themeExists('stable'));

    $this->runUpdates();

    // Refresh the theme handler now that Stable has been installed.
    $this->themeHandler->refreshInfo();
    $this->assertTrue($this->themeHandler->themeExists('stable'));
  }

}

class VfsThemeExtensionList extends ThemeExtensionList {

  /**
   * The extension discovery for this extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected $extensionDiscovery;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $root, string $type, CacheBackendInterface $cache, InfoParserInterface $info_parser, ModuleHandlerInterface $module_handler, StateInterface $state, ConfigFactoryInterface $config_factory, ThemeEngineExtensionList $engine_list, $install_profile) {
    parent::__construct($root, $type, $cache, $info_parser, $module_handler, $state, $config_factory, $engine_list, $install_profile);
    $this->extensionDiscovery = new ExtensionDiscovery('vfs://core');
    $this->infoParser = new VfsInfoParser('vfs:/');
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
