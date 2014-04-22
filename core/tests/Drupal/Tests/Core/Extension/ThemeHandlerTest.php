<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Extension\ThemeHandlerTest.
 */

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Config\ConfigInstaller;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the theme handler.
 *
 * @group Drupal
 * @group Theme
 *
 * @see \Drupal\Core\Extension\ThemeHandler
 */
class ThemeHandlerTest extends UnitTestCase {

  /**
   * The mocked route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilder|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeBuilder;

  /**
   * The mocked info parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $infoParser;

  /**
   * The mocked state backend.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $state;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked config installer.
   *
   * @var \Drupal\Core\Config\ConfigInstaller|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configInstaller;

  /**
   * The extension discovery.
   *
   * @var \Drupal\Core\Extension\ExtensionDiscovery|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $extensionDiscovery;

  /**
   * The tested theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler|\Drupal\Tests\Core\Extension\TestThemeHandler
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Theme handler',
      'description' => 'Tests the theme handler.',
      'group' => 'Theme',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->configFactory = $this->getConfigFactoryStub(array(
      'core.extension' => array(
        'module' => array(),
        'theme' => array(),
        'disabled' => array(
          'theme' => array(),
        ),
      ),
    ));
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->state = new State(new KeyValueMemoryFactory());
    $this->infoParser = $this->getMock('Drupal\Core\Extension\InfoParserInterface');
    $this->configInstaller = $this->getMock('Drupal\Core\Config\ConfigInstallerInterface');
    $this->routeBuilder = $this->getMockBuilder('Drupal\Core\Routing\RouteBuilder')
      ->disableOriginalConstructor()
      ->getMock();
    $this->extensionDiscovery = $this->getMockBuilder('Drupal\Core\Extension\ExtensionDiscovery')
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeHandler = new TestThemeHandler($this->configFactory, $this->moduleHandler, $this->state, $this->infoParser, $this->configInstaller, $this->routeBuilder, $this->extensionDiscovery);

    $cache_backend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->getContainerWithCacheBins($cache_backend);
  }

  /**
   * Tests rebuilding the theme data.
   *
   * @see \Drupal\Core\Extension\ThemeHandler::rebuildThemeData()
   */
  public function testRebuildThemeData() {
    $this->extensionDiscovery->expects($this->at(0))
      ->method('scan')
      ->with('theme')
      ->will($this->returnValue(array(
        'seven' => new Extension('theme', DRUPAL_ROOT . '/core/themes/seven/seven.info.yml', 'seven.theme'),
      )));
    $this->extensionDiscovery->expects($this->at(1))
      ->method('scan')
      ->with('theme_engine')
      ->will($this->returnValue(array(
        'twig' => new Extension('theme_engine', DRUPAL_ROOT . '/core/themes/engines/twig/twig.info.yml', 'twig.engine'),
      )));
    $this->infoParser->expects($this->once())
      ->method('parse')
      ->with(DRUPAL_ROOT . '/core/themes/seven/seven.info.yml')
      ->will($this->returnCallback(function ($file) {
        $info_parser = new InfoParser();
        return $info_parser->parse($file);
      }));
    $this->moduleHandler->expects($this->once())
      ->method('buildModuleDependencies')
      ->will($this->returnArgument(0));

    $this->moduleHandler->expects($this->once())
      ->method('alter');

    $theme_data = $this->themeHandler->rebuildThemeData();
    $this->assertCount(1, $theme_data);
    $info = $theme_data['seven'];

    // Ensure some basic properties.
    $this->assertInstanceOf('Drupal\Core\Extension\Extension', $info);
    $this->assertEquals('seven', $info->getName());
    $this->assertEquals(DRUPAL_ROOT . '/core/themes/seven/seven.info.yml', $info->getPathname());
    $this->assertEquals(DRUPAL_ROOT . '/core/themes/seven/seven.theme', $info->getExtensionPathname());
    $this->assertEquals(DRUPAL_ROOT . '/core/themes/engines/twig/twig.engine', $info->owner);
    $this->assertEquals('twig', $info->prefix);

    $this->assertEquals('twig', $info->info['engine']);
    $this->assertEquals(array(), $info->info['libraries']);

    // Ensure that the css paths are set with the proper prefix.
    $this->assertEquals(array(
      'screen' => array(
        'seven.base.css' => DRUPAL_ROOT . '/core/themes/seven/seven.base.css',
        'style.css' => DRUPAL_ROOT . '/core/themes/seven/style.css',
        'css/components/buttons.css' => DRUPAL_ROOT . '/core/themes/seven/css/components/buttons.css',
        'css/components/buttons.theme.css' => DRUPAL_ROOT . '/core/themes/seven/css/components/buttons.theme.css',
        'css/components/tour.theme.css' => DRUPAL_ROOT . '/core/themes/seven/css/components/tour.theme.css',
      ),
    ), $info->info['stylesheets']);
    $this->assertEquals(DRUPAL_ROOT . '/core/themes/seven/screenshot.png', $info->info['screenshot']);
  }

  /**
   * Tests rebuild the theme data with theme parents.
   */
  public function testRebuildThemeDataWithThemeParents() {
    $this->extensionDiscovery->expects($this->at(0))
      ->method('scan')
      ->with('theme')
      ->will($this->returnValue(array(
        'test_subtheme' => new Extension('theme', DRUPAL_ROOT . '/core/modules/system/tests/themes/test_subtheme/test_subtheme.info.yml', 'test_subtheme.info.yml'),
        'test_basetheme' => new Extension('theme', DRUPAL_ROOT . '/core/modules/system/tests/themes/test_basetheme/test_basetheme.info.yml', 'test_basetheme.info.yml'),
      )));
    $this->extensionDiscovery->expects($this->at(1))
      ->method('scan')
      ->with('theme_engine')
      ->will($this->returnValue(array(
        'twig' => new Extension('theme_engine', DRUPAL_ROOT . '/core/themes/engines/twig/twig.info.yml', 'twig.engine'),
      )));
    $this->infoParser->expects($this->at(0))
      ->method('parse')
      ->with(DRUPAL_ROOT . '/core/modules/system/tests/themes/test_subtheme/test_subtheme.info.yml')
      ->will($this->returnCallback(function ($file) {
        $info_parser = new InfoParser();
        return $info_parser->parse($file);
      }));
    $this->infoParser->expects($this->at(1))
      ->method('parse')
      ->with(DRUPAL_ROOT . '/core/modules/system/tests/themes/test_basetheme/test_basetheme.info.yml')
      ->will($this->returnCallback(function ($file) {
        $info_parser = new InfoParser();
        return $info_parser->parse($file);
      }));
    $this->moduleHandler->expects($this->once())
      ->method('buildModuleDependencies')
      ->will($this->returnArgument(0));

    $theme_data = $this->themeHandler->rebuildThemeData();
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
    $info_basetheme->sub_themes = array('test_subtheme');

    $this->assertEquals(DRUPAL_ROOT . '/core/themes/engines/twig/twig.engine', $info_basetheme->owner);
    $this->assertEquals('twig', $info_basetheme->prefix);
    $this->assertEquals(DRUPAL_ROOT . '/core/themes/engines/twig/twig.engine', $info_subtheme->owner);
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
    $base_themes = $this->themeHandler->getBaseThemes($themes, $theme);
    $this->assertEquals($expected, $base_themes);
  }

  /**
   * Provides test data for testGetBaseThemes.
   *
   * @return array
   *   An array of theme test data.
   */
  public function providerTestGetBaseThemes() {
    $data = array();

    // Tests a theme without any base theme.
    $themes = array();
    $themes['test_1'] = (object) array(
      'name' => 'test_1',
      'info' => array(
        'name' => 'test_1',
      ),
    );
    $data[] = array($themes, 'test_1', array());

    // Tests a theme with a non existing base theme.
    $themes = array();
    $themes['test_1'] = (object) array(
      'name' => 'test_1',
      'info' => array(
        'name' => 'test_1',
        'base theme' => 'test_2',
      ),
    );
    $data[] = array($themes, 'test_1', array('test_2' => NULL));

    // Tests a theme with a single existing base theme.
    $themes = array();
    $themes['test_1'] = (object) array(
      'name' => 'test_1',
      'info' => array(
        'name' => 'test_1',
        'base theme' => 'test_2',
      ),
    );
    $themes['test_2'] = (object) array(
      'name' => 'test_2',
      'info' => array(
        'name' => 'test_2',
      ),
    );
    $data[] = array($themes, 'test_1', array('test_2' => 'test_2'));

    // Tests a theme with multiple base themes.
    $themes = array();
    $themes['test_1'] = (object) array(
      'name' => 'test_1',
      'info' => array(
        'name' => 'test_1',
        'base theme' => 'test_2',
      ),
    );
    $themes['test_2'] = (object) array(
      'name' => 'test_2',
      'info' => array(
        'name' => 'test_2',
        'base theme' => 'test_3',
      ),
    );
    $themes['test_3'] = (object) array(
      'name' => 'test_3',
      'info' => array(
        'name' => 'test_3',
      ),
    );
    $data[] = array(
      $themes,
      'test_1',
      array('test_2' => 'test_2', 'test_3' => 'test_3'),
    );

    return $data;
  }

}

/**
 * Extends the default theme handler to mock some drupal_ methods.
 */
class TestThemeHandler extends ThemeHandler {

  /**
   * {@inheritdoc}
   */
  protected function clearCssCache() {
    $this->clearedCssCache = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function themeRegistryRebuild() {
    $this->registryRebuild = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function systemThemeList() {
    return $this->systemList;
  }

  /**
   * {@inheritdoc}
   */
  protected function systemListReset() {
  }

}

if (!defined('DRUPAL_EXTENSION_NAME_MAX_LENGTH')) {
  define('DRUPAL_EXTENSION_NAME_MAX_LENGTH', 50);
}
if (!defined('DRUPAL_PHP_FUNCTION_PATTERN')) {
  define('DRUPAL_PHP_FUNCTION_PATTERN', '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*');
}
if (!defined('DRUPAL_ROOT')) {
  define('DRUPAL_ROOT', dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)))));
}
if (!defined('DRUPAL_MINIMUM_PHP')) {
  define('DRUPAL_MINIMUM_PHP', '5.3.10');
}
