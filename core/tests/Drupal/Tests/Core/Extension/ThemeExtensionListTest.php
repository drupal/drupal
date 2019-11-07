<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeEngineExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ThemeExtensionList
 * @group Extension
 */
class ThemeExtensionListTest extends UnitTestCase {

  /**
   * Tests rebuild the theme data with theme parents.
   */
  public function testRebuildThemeDataWithThemeParents() {
    $extension_discovery = $this->prophesize(ExtensionDiscovery::class);
    $extension_discovery
      ->scan('theme')
      ->willReturn([
        'test_subtheme'  => new Extension($this->root, 'theme', 'core/modules/system/tests/themes/test_subtheme/test_subtheme.info.yml', 'test_subtheme.info.yml'),
        'test_basetheme' => new Extension($this->root, 'theme', 'core/modules/system/tests/themes/test_basetheme/test_basetheme.info.yml', 'test_basetheme.info.yml'),
      ]);
    $extension_discovery
      ->scan('theme_engine')
      ->willReturn([
        'twig' => new Extension($this->root, 'theme_engine', 'core/themes/engines/twig/twig.info.yml', 'twig.engine'),
      ]);

    // Verify that info parser is called with the specified paths.
    $argument_condition = function ($path) {
      return in_array($path, [
        'core/modules/system/tests/themes/test_subtheme/test_subtheme.info.yml',
        'core/modules/system/tests/themes/test_basetheme/test_basetheme.info.yml',
        'core/themes/engines/twig/twig.info.yml',
      ], TRUE);
    };
    $info_parser = $this->prophesize(InfoParserInterface::class);
    $root = $this->root;
    $info_parser->parse(Argument::that($argument_condition))
      ->shouldBeCalled()
      ->will(function ($file) use ($root) {
        $info_parser = new InfoParser($root);
        return $info_parser->parse($root . '/' . $file[0]);
      });

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler
      ->buildModuleDependencies(Argument::type('array'))
      ->willReturnArgument(0);
    $module_handler
      ->alter('system_info', Argument::type('array'), Argument::type(Extension::class), Argument::any())
      ->shouldBeCalled();

    $state = new State(new KeyValueMemoryFactory(), new MemoryBackend(), new NullLockBackend());

    $config_factory = $this->getConfigFactoryStub([
      'core.extension' => [
        'module' => [],
        'theme' => [],
        'disabled' => [
          'theme' => [],
        ],
        'theme_engine' => '',
      ],
    ]);

    $theme_engine_list = new TestThemeEngineExtensionList($this->root, 'theme_engine', new NullBackend('test'), $info_parser->reveal(), $module_handler->reveal(), $state, $config_factory, 'testing');
    $theme_engine_list->setExtensionDiscovery($extension_discovery->reveal());

    $theme_list = new TestThemeExtensionList($this->root, 'theme', new NullBackend('test'), $info_parser->reveal(), $module_handler->reveal(), $state, $config_factory, $theme_engine_list, 'testing');
    $theme_list->setExtensionDiscovery($extension_discovery->reveal());

    $theme_data = $theme_list->reset()->getList();
    $this->assertCount(2, $theme_data);

    $info_basetheme = $theme_data['test_basetheme'];
    $info_subtheme = $theme_data['test_subtheme'];

    // Ensure some basic properties.
    $this->assertInstanceOf('Drupal\Core\Extension\Extension', $info_basetheme);
    $this->assertEquals('test_basetheme', $info_basetheme->getName());
    $this->assertInstanceOf('Drupal\Core\Extension\Extension', $info_subtheme);
    $this->assertEquals('test_subtheme', $info_subtheme->getName());

    // Test the parent/child-theme properties.
    $info_subtheme->info['base theme'] = 'test_basetheme';
    $info_basetheme->sub_themes = ['test_subtheme'];

    $this->assertEquals('core/themes/engines/twig/twig.engine', $info_basetheme->owner);
    $this->assertEquals('twig', $info_basetheme->prefix);
    $this->assertEquals('core/themes/engines/twig/twig.engine', $info_subtheme->owner);
    $this->assertEquals('twig', $info_subtheme->prefix);
  }

  /**
   * Tests getting the base themes for a set a defines themes.
   *
   * @param array $themes
   *   An array of available themes, keyed by the theme name.
   * @param string $theme
   *   The theme name to find all its base themes.
   * @param array $expected
   *   The expected base themes.
   *
   * @dataProvider providerTestGetBaseThemes
   */
  public function testGetBaseThemes(array $themes, $theme, array $expected) {
    // Mocks and stubs.
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $state = new State(new KeyValueMemoryFactory(), new MemoryBackend(), new NullLockBackend());
    $config_factory = $this->getConfigFactoryStub([]);
    $theme_engine_list = $this->prophesize(ThemeEngineExtensionList::class);
    $theme_listing = new ThemeExtensionList($this->root, 'theme', new NullBackend('test'), new InfoParser($this->root), $module_handler->reveal(), $state, $config_factory, $theme_engine_list->reveal(), 'test');

    $base_themes = $theme_listing->getBaseThemes($themes, $theme);

    $this->assertEquals($expected, $base_themes);
  }

  /**
   * Provides test data for testGetBaseThemes.
   *
   * @return array
   *   An array of theme test data.
   */
  public function providerTestGetBaseThemes() {
    $data = [];

    // Tests a theme without any base theme.
    $themes = [];
    $themes['test_1'] = (object) [
      'name' => 'test_1',
      'info' => [
        'name' => 'test_1',
      ],
    ];
    $data[] = [$themes, 'test_1', []];

    // Tests a theme with a non existing base theme.
    $themes = [];
    $themes['test_1'] = (object) [
      'name' => 'test_1',
      'info' => [
        'name'       => 'test_1',
        'base theme' => 'test_2',
      ],
    ];
    $data[] = [$themes, 'test_1', ['test_2' => NULL]];

    // Tests a theme with a single existing base theme.
    $themes = [];
    $themes['test_1'] = (object) [
      'name' => 'test_1',
      'info' => [
        'name'       => 'test_1',
        'base theme' => 'test_2',
      ],
    ];
    $themes['test_2'] = (object) [
      'name' => 'test_2',
      'info' => [
        'name' => 'test_2',
      ],
    ];
    $data[] = [$themes, 'test_1', ['test_2' => 'test_2']];

    // Tests a theme with multiple base themes.
    $themes = [];
    $themes['test_1'] = (object) [
      'name' => 'test_1',
      'info' => [
        'name'       => 'test_1',
        'base theme' => 'test_2',
      ],
    ];
    $themes['test_2'] = (object) [
      'name' => 'test_2',
      'info' => [
        'name'       => 'test_2',
        'base theme' => 'test_3',
      ],
    ];
    $themes['test_3'] = (object) [
      'name' => 'test_3',
      'info' => [
        'name' => 'test_3',
      ],
    ];
    $data[] = [
      $themes,
      'test_1',
      ['test_2' => 'test_2', 'test_3' => 'test_3'],
    ];

    return $data;
  }

}

/**
 * Trait that allows extension discovery to be set.
 */
trait SettableDiscoveryExtensionListTrait {

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
   */
  public function setExtensionDiscovery(ExtensionDiscovery $discovery) {
    $this->extensionDiscovery = $discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionDiscovery() {
    return $this->extensionDiscovery;
  }

}

/**
 * Test theme extension list class.
 */
class TestThemeExtensionList extends ThemeExtensionList {

  use SettableDiscoveryExtensionListTrait;

}

/**
 * Test theme engine extension list class.
 */
class TestThemeEngineExtensionList extends ThemeEngineExtensionList {

  use SettableDiscoveryExtensionListTrait;

}

if (!defined('DRUPAL_MINIMUM_PHP')) {
  define('DRUPAL_MINIMUM_PHP', '5.5.9');
}
