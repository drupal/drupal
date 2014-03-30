<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Asset\LibraryDiscoveryTest.
 */

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Cache\Cache;
use Drupal\Tests\UnitTestCase;

if (!defined('DRUPAL_ROOT')) {
  define('DRUPAL_ROOT', dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)))));
}

if (!defined('CSS_AGGREGATE_DEFAULT')) {
  define('CSS_AGGREGATE_DEFAULT', 0);
  define('CSS_AGGREGATE_THEME', 100);
  define('CSS_BASE', -200);
  define('CSS_LAYOUT', -100);
  define('CSS_COMPONENT', 0);
  define('CSS_STATE', 100);
  define('CSS_THEME', 200);
  define('JS_SETTING', -200);
  define('JS_LIBRARY', -100);
  define('JS_DEFAULT', 0);
  define('JS_THEME', 100);
}

/**
 * Tests the library discovery.
 *
 * @coversDefaultClass \Drupal\Core\Asset\LibraryDiscovery
 */
class LibraryDiscoveryTest extends UnitTestCase {

  /**
   * The tested library provider.
   *
   * @var \Drupal\Core\Asset\LibraryDiscovery|\Drupal\Tests\Core\Asset\TestLibraryDiscovery
   */
  protected $libraryDiscovery;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tests \Drupal\Core\Asset\LibraryProvider',
      'description' => '',
      'group' => 'Asset handling',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->themeHandler = $this->getMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $this->libraryDiscovery = new TestLibraryDiscovery($this->cache, $this->moduleHandler, $this->themeHandler);
  }

  /**
   * Tests that basic functionality works for getLibraryByName.
   *
   * @covers ::getLibraryByName()
   * @covers ::buildLibrariesByExtension()
   */
  public function testGetLibraryByNameSimple() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'example_module', $path);

    $library = $this->libraryDiscovery->getLibraryByName('example_module', 'example');
    $this->assertCount(0, $library['js']);
    $this->assertCount(1, $library['css']);
    $this->assertCount(0, $library['dependencies']);
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);

    // Ensures that VERSION is replaced by the current core version.
    $this->assertEquals(\Drupal::VERSION, $library['version']);
  }

  /**
   * Tests that basic functionality works for getLibrariesByExtension.
   *
   * @covers ::getLibrariesByExtension()
   * @covers ::buildLibrariesByExtension()
   */
  public function testGetLibrariesByExtensionSimple() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'example_module', $path);

    $libraries = $this->libraryDiscovery->getLibrariesByExtension('example_module', 'example');
    $this->assertCount(1, $libraries);
    $this->assertEquals($path . '/css/example.css', $libraries['example']['css'][0]['data']);
  }

  /**
   * Tests that a theme can be used instead of a module.
   *
   * @covers ::getLibraryByName()
   * @covers ::buildLibrariesByExtension()
   */
  public function testGetLibraryByNameWithTheme() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_theme')
      ->will($this->returnValue(FALSE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('theme', 'example_theme', $path);

    $library = $this->libraryDiscovery->getLibraryByName('example_theme', 'example');
    $this->assertCount(0, $library['js']);
    $this->assertCount(1, $library['css']);
    $this->assertCount(0, $library['dependencies']);
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);
  }

  /**
   * Tests that a module with a missing library file results in FALSE.
   *
   * @covers ::getLibraryByName()
   * @covers ::getLibrariesByExtension()
   * @covers ::buildLibrariesByExtension()
   */
  public function testGetLibraryWithMissingLibraryFile() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files_not_existing';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'example_module', $path);

    $this->assertFalse($this->libraryDiscovery->getLibraryByName('example_module', 'example'));
    $this->assertFalse($this->libraryDiscovery->getLibrariesByExtension('example_module'));
  }

  /**
   * Tests that an exception is thrown when a libraries file couldn't be parsed.
   *
   * @expectedException \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *
   * @covers ::buildLibrariesByExtension()
   */
  public function testInvalidLibrariesFile() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('invalid_file')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'invalid_file', $path);

    $this->libraryDiscovery->getLibrariesByExtension('invalid_file');
  }

  /**
   * Tests that an exception is thrown when no CSS/JS/setting is specified.
   *
   * @expectedException \Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException
   * @expectedExceptionMessage Incomplete library definition for 'example' in core/tests/Drupal/Tests/Core/Asset/library_test_files/example_module_missing_information.libraries.yml
   *
   * @covers ::buildLibrariesByExtension()
   */
  public function testGetLibraryWithMissingInformation() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module_missing_information')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'example_module_missing_information', $path);

    $this->libraryDiscovery->getLibrariesByExtension('example_module_missing_information');
  }

  /**
   * Tests that the version property of external libraries is handled.
   *
   * @covers ::buildLibrariesByExtension()
   */
  public function testExternalLibraries() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('external')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'external', $path);

    $library = $this->libraryDiscovery->getLibraryByName('external', 'example_external');
    $this->assertEquals($path . '/css/example_external.css', $library['css'][0]['data']);
    $this->assertEquals('3.14', $library['version']);
  }

  /**
   * Ensures that CSS weights are taken into account properly.
   *
   * @covers ::buildLibrariesByExtension()
   */
  public function testDefaultCssWeights() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('css_weights')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'css_weights', $path);

    $library = $this->libraryDiscovery->getLibraryByName('css_weights', 'example');
    $css = $library['css'];
    $this->assertCount(10, $css);

    // The following default weights are tested:
    // - CSS_BASE: -200
    // - CSS_LAYOUT: -100
    // - CSS_COMPONENT: 0
    // - CSS_STATE: 100
    // - CSS_THEME: 200
    $this->assertEquals(200, $css[0]['weight']);
    $this->assertEquals(200 + 29, $css[1]['weight']);
    $this->assertEquals(-200, $css[2]['weight']);
    $this->assertEquals(-200 + 97, $css[3]['weight']);
    $this->assertEquals(-100, $css[4]['weight']);
    $this->assertEquals(-100 + 92, $css[5]['weight']);
    $this->assertEquals(0, $css[6]['weight']);
    $this->assertEquals(45, $css[7]['weight']);
    $this->assertEquals(100, $css[8]['weight']);
    $this->assertEquals(100 + 8, $css[9]['weight']);
  }

  /**
   * Ensures that you cannot provide positive weights for JavaScript libraries.
   *
   * @expectedException \UnexpectedValueException
   *
   * @covers ::buildLibrariesByExtension()
   */
  public function testJsWithPositiveWeight() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('js_positive_weight')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'js_positive_weight', $path);

    $this->libraryDiscovery->getLibrariesByExtension('js_positive_weight');
  }

  /**
   * Tests a library with CSS/JavaScript and a setting.
   *
   * @covers ::buildLibrariesByExtension()
   */
  public function testLibraryWithCssJsSetting() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('css_js_settings')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'css_js_settings', $path);

    $library = $this->libraryDiscovery->getLibraryByName('css_js_settings', 'example');

    // Ensures that the group and type are set automatically.
    $this->assertEquals(-100, $library['js'][0]['group']);
    $this->assertEquals('file', $library['js'][0]['type']);
    $this->assertEquals($path . '/js/example.js', $library['js'][0]['data']);

    $this->assertEquals(0, $library['css'][0]['group']);
    $this->assertEquals('file', $library['css'][0]['type']);
    $this->assertEquals($path . '/css/base.css', $library['css'][0]['data']);

    $this->assertEquals('setting', $library['js'][1]['type']);
    $this->assertEquals(array('key' => 'value'), $library['js'][1]['data']);
  }

  /**
   * Tests a library with dependencies.
   *
   * @covers ::buildLibrariesByExtension()
   */
  public function testLibraryWithDependencies() {
     $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('dependencies')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'dependencies', $path);

    $library = $this->libraryDiscovery->getLibraryByName('dependencies', 'example');
    $this->assertCount(2, $library['dependencies']);
    $this->assertEquals('external/example_external', $library['dependencies'][0]);
    $this->assertEquals('example_module/example', $library['dependencies'][1]);
  }

  /**
   * Tests a library with a couple of data formats like full URL.
   *
   * @covers ::buildLibrariesByExtension()
   */
  public function testLibraryWithDataTypes() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('data_types')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'data_types', $path);

    $this->libraryDiscovery->setFileValidUri('public://test.css', TRUE);
    $this->libraryDiscovery->setFileValidUri('public://test2.css', FALSE);

    $library = $this->libraryDiscovery->getLibraryByName('data_types', 'example');
    $this->assertCount(5, $library['css']);
    $this->assertEquals('external', $library['css'][0]['type']);
    $this->assertEquals('http://example.com/test.css', $library['css'][0]['data']);
    $this->assertEquals('file', $library['css'][1]['type']);
    $this->assertEquals('tmp/test.css', $library['css'][1]['data']);
    $this->assertEquals('external', $library['css'][2]['type']);
    $this->assertEquals('//cdn.com/test.css', $library['css'][2]['data']);
    $this->assertEquals('file', $library['css'][3]['type']);
    $this->assertEquals('public://test.css', $library['css'][3]['data']);
  }

  /**
   * Tests the internal static cache.
   *
   * @covers ::ensureLibraryInformation()
   */
  public function testStaticCache() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('example_module')
      ->will($this->returnValue(TRUE));
    $this->cache->expects($this->once())
      ->method('get')
      ->with('library:info:' . 'example_module')
      ->will($this->returnValue(NULL));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'example_module', $path);

    $library = $this->libraryDiscovery->getLibraryByName('example_module', 'example');
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);

    $library = $this->libraryDiscovery->getLibraryByName('example_module', 'example');
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);
  }

  /**
   * Tests the external cache.
   *
   * @covers ::getCache()
   */
  public function testExternalCache() {
    // Ensure that the module handler does not need to be touched.
    $this->moduleHandler->expects($this->never())
      ->method('moduleExists');

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);

    // Setup a cache entry which will be retrieved, but just once, so the static
    // cache still works.
    $this->cache->expects($this->once())
      ->method('get')
      ->with('library:info:' . 'example_module')
      ->will($this->returnValue((object) array(
        'data' => array(
          'example' => array(
            'css' => array(
              array(
                'data' => $path . '/css/example.css',
              ),
            ),
          ),
        )
      )));

    $library = $this->libraryDiscovery->getLibraryByName('example_module', 'example');
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);

    $library = $this->libraryDiscovery->getLibraryByName('example_module', 'example');
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);
  }

  /**
   * Tests setting the external cache.
   *
   * @covers ::setCache()
   */
  public function testSetCache() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('example_module')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscovery->setPaths('module', 'example_module', $path);

    $this->cache->expects($this->once())
      ->method('set')
      ->with('library:info:example_module', $this->isType('array'), Cache::PERMANENT, array(
      'extension' => array(TRUE, 'example_module'),
      'library_info' => array(TRUE),
    ));

    $library = $this->libraryDiscovery->getLibraryByName('example_module', 'example');
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);
  }

}

/**
 * Wraps the tested class to mock the external dependencies.
 */
class TestLibraryDiscovery extends LibraryDiscovery {

  protected $paths;

  protected $validUris;

  protected function drupalGetPath($type, $name) {
    return isset($this->paths[$type][$name]) ? $this->paths[$type][$name] : NULL;
  }

  public function setPaths($type, $name, $path) {
    $this->paths[$type][$name] = $path;
  }

  protected function fileValidUri($source) {
    return isset($this->validUris[$source]) ? $this->validUris[$source] : FALSE;
  }

  public function setFileValidUri($source, $valid) {
    $this->validUris[$source] = $valid;
  }

}
