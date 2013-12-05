<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Extension\ThemeHandlerTest.
 */

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\ThemeHandler;
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
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The system listing info.
   *
   * @var \Drupal\Core\SystemListingInfo|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $systemListingInfo;

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
    $this->configFactory = $this->getConfigFactoryStub(array('system.theme' => array(), 'system.theme.disabled' => array()));
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->infoParser = $this->getMock('Drupal\Core\Extension\InfoParserInterface');
    $this->routeBuilder = $this->getMockBuilder('Drupal\Core\Routing\RouteBuilder')
      ->disableOriginalConstructor()
      ->getMock();
    $this->systemListingInfo = $this->getMockBuilder('Drupal\Core\SystemListingInfo')
      ->disableOriginalConstructor()
      ->getMock();

    $this->themeHandler = new TestThemeHandler($this->configFactory, $this->moduleHandler, $this->cacheBackend, $this->infoParser, $this->routeBuilder, $this->systemListingInfo);
  }

  /**
   * Tests enabling a theme with a name longer than 50 chars.
   *
   * @expectedException \Drupal\Core\Extension\ExtensionNameLengthException
   * @expectedExceptionMessage Theme name <em class="placeholder">thisNameIsFarTooLong0000000000000000000000000000051</em> is over the maximum allowed length of 50 characters.
   */
  public function testThemeEnableWithTooLongName() {
    $this->themeHandler->enable(array('thisNameIsFarTooLong0000000000000000000000000000051'));
  }

  /**
   * Tests enabling a single theme.
   *
   * @see \Drupal\Core\Extension\ThemeHandler::enable()
   */
  public function testEnableSingleTheme() {
    $theme_list = array('theme_test');

    $this->configFactory->get('system.theme')
      ->expects($this->once())
      ->method('set')
      ->with('enabled.theme_test', 0)
      ->will($this->returnSelf());
    $this->configFactory->get('system.theme')
      ->expects($this->once())
      ->method('save');

    $this->configFactory->get('system.theme.disabled')
      ->expects($this->once())
      ->method('clear')
      ->with('theme_test')
      ->will($this->returnSelf());
    $this->configFactory->get('system.theme.disabled')
      ->expects($this->once())
      ->method('save');

    $this->systemListingInfo->expects($this->any())
      ->method('scan')
      ->will($this->returnValue(array()));

    // Ensure that the themes_enabled hook is fired.
    $this->moduleHandler->expects($this->at(0))
      ->method('invokeAll')
      ->with('system_theme_info')
      ->will($this->returnValue(array()));

    $this->moduleHandler->expects($this->at(1))
      ->method('invokeAll')
      ->with('themes_enabled', array($theme_list));

    $this->themeHandler->enable($theme_list);

    $this->assertTrue($this->themeHandler->clearedCssCache);
    $this->assertTrue($this->themeHandler->registryRebuild);
    $this->assertTrue($this->themeHandler->installedDefaultConfig['theme_test']);
  }

  /**
   * Ensures that enabling a theme does clear the theme info listing.
   *
   * @see \Drupal\Core\Extension\ThemeHandler::listInfo()
   */
  public function testEnableAndListInfo() {
    $this->configFactory->get('system.theme')
      ->expects($this->exactly(2))
      ->method('set')
      ->will($this->returnSelf());

    $this->configFactory->get('system.theme.disabled')
      ->expects($this->exactly(2))
      ->method('clear')
      ->will($this->returnSelf());

    $this->systemListingInfo->expects($this->any())
      ->method('scan')
      ->will($this->returnValue(array()));

    $this->themeHandler->enable(array('bartik'));
    $this->themeHandler->systemList['bartik'] = (object) array(
      'name' => 'bartik',
      'info' => array(
        'stylesheets' => array(
          'all' => array(
            'css/layout.css',
            'css/style.css',
            'css/colors.css',
          ),
        ),
        'scripts' => array(
          'example' => 'theme.js',
        ),
        'engine' => 'twig',
        'base theme' => 'stark',
      ),
    );

    $list_info = $this->themeHandler->listInfo();
    $this->assertCount(1, $list_info);

    $this->assertEquals($this->themeHandler->systemList['bartik']->info['stylesheets'], $list_info['bartik']->stylesheets);
    $this->assertEquals($this->themeHandler->systemList['bartik']->scripts, $list_info['bartik']->scripts);
    $this->assertEquals('twig', $list_info['bartik']->engine);
    $this->assertEquals('stark', $list_info['bartik']->base_theme);
    $this->assertEquals(0, $list_info['bartik']->status);

    $this->themeHandler->systemList['seven'] = (object) array(
      'name' => 'seven',
      'info' => array(
        'stylesheets' => array(
          'screen' => array(
            'style.css',
          ),
        ),
        'scripts' => array(),
      ),
      'status' => 1,
    );

    $this->themeHandler->enable(array('seven'));

    $list_info = $this->themeHandler->listInfo();
    $this->assertCount(2, $list_info);

    $this->assertEquals($this->themeHandler->systemList['seven']->info['stylesheets'], $list_info['seven']->stylesheets);
    $this->assertEquals(1, $list_info['seven']->status);
  }

  /**
   * Tests rebuilding the theme data.
   *
   * @see \Drupal\Core\Extension\ThemeHandler::rebuildThemeData()
   */
  public function testRebuildThemeData() {
    $this->systemListingInfo->expects($this->at(0))
      ->method('scan')
      ->with($this->anything(), 'themes', 'name', 1)
      ->will($this->returnValue(array(
        'seven' => (object) array(
          'name' => 'seven',
          'uri' => DRUPAL_ROOT . '/core/themes/seven/seven.info.yml',
        ),
      )));
    $this->infoParser->expects($this->once())
      ->method('parse')
      ->with(DRUPAL_ROOT . '/core/themes/seven/seven.info.yml')
      ->will($this->returnCallback(function ($file) {
        $info_parser = new InfoParser();
        return $info_parser->parse($file);
      }));

    $this->moduleHandler->expects($this->once())
      ->method('alter');

    $theme_data = $this->themeHandler->rebuildThemeData();
    $this->assertCount(1, $theme_data);
    $info = $theme_data['seven'];

    // Ensure some basic properties.
    $this->assertInstanceOf('stdClass', $info);
    $this->assertEquals('seven', $info->name);
    $this->assertEquals(DRUPAL_ROOT . '/core/themes/seven/seven.info.yml', $info->uri);
    $this->assertEquals(DRUPAL_ROOT . '/core/themes/seven/seven.info.yml', $info->filename);

    $this->assertEquals('twig', $info->info['engine']);
    $this->assertEquals(array(), $info->info['scripts']);

    // Ensure that the css paths are set with the proper prefix.
    $this->assertEquals(array(
      'screen' => array(
        'style.css' => DRUPAL_ROOT . '/core/themes/seven/style.css',
        'css/components/buttons.css' => DRUPAL_ROOT . '/core/themes/seven/css/components/buttons.css',
        'css/components/buttons.theme.css' => DRUPAL_ROOT . '/core/themes/seven/css/components/buttons.theme.css',
      ),
    ), $info->info['stylesheets']);
    $this->assertEquals(DRUPAL_ROOT . '/core/themes/seven/screenshot.png', $info->info['screenshot']);
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
  protected function configInstallDefaultConfig($theme) {
    $this->installedDefaultConfig[$theme] = TRUE;
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
