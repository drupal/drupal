<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Asset\LibraryDiscoveryTest.
 */

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\LibraryDiscoveryParser;
use Drupal\Tests\UnitTestCase;

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
 * @coversDefaultClass \Drupal\Core\Asset\LibraryDiscoveryParser
 * @group Asset
 */
class LibraryDiscoveryParserTest extends UnitTestCase {

  /**
   * The tested library provider.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryParser|\Drupal\Tests\Core\Asset\TestLibraryDiscoveryParser
   */
  protected $libraryDiscoveryParser;

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
   * The mocked lock backend..
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $lock;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->libraryDiscoveryParser = new TestLibraryDiscoveryParser($this->moduleHandler);
  }

  /**
   * Tests that basic functionality works for getLibraryByName.
   *
   * @covers ::buildByExtension()
   */
  public function testBuildByExtensionSimple() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'example_module', $path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('example_module', 'example');
    $library = $libraries['example'];

    $this->assertCount(0, $library['js']);
    $this->assertCount(1, $library['css']);
    $this->assertCount(0, $library['dependencies']);
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);

    // Ensures that VERSION is replaced by the current core version.
    $this->assertEquals(\Drupal::VERSION, $library['version']);
  }

  /**
   * Tests that a theme can be used instead of a module.
   *
   * @covers ::buildByExtension()
   */
  public function testBuildByExtensionWithTheme() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_theme')
      ->will($this->returnValue(FALSE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('theme', 'example_theme', $path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('example_theme');
    $library = $libraries['example'];

    $this->assertCount(0, $library['js']);
    $this->assertCount(1, $library['css']);
    $this->assertCount(0, $library['dependencies']);
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);
  }

  /**
   * Tests that a module with a missing library file results in FALSE.
   *
   * @covers ::buildByExtension()
   */
  public function testBuildByExtensionWithMissingLibraryFile() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files_not_existing';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'example_module', $path);

    $this->assertSame($this->libraryDiscoveryParser->buildByExtension('example_module'), array());
  }

  /**
   * Tests that an exception is thrown when a libraries file couldn't be parsed.
   *
   * @expectedException \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *
   * @covers ::buildByExtension()
   */
  public function testInvalidLibrariesFile() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('invalid_file')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'invalid_file', $path);

    $this->libraryDiscoveryParser->buildByExtension('invalid_file');
  }

  /**
   * Tests that an exception is thrown when no CSS/JS/setting is specified.
   *
   * @expectedException \Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException
   * @expectedExceptionMessage Incomplete library definition for 'example' in core/tests/Drupal/Tests/Core/Asset/library_test_files/example_module_missing_information.libraries.yml
   *
   * @covers ::buildByExtension()
   */
  public function testBuildByExtensionWithMissingInformation() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module_missing_information')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'example_module_missing_information', $path);

    $this->libraryDiscoveryParser->buildByExtension('example_module_missing_information');
  }

  /**
   * Tests that the version property of external libraries is handled.
   *
   * @covers ::buildByExtension()
   */
  public function testExternalLibraries() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('external')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'external', $path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('external', 'example_external');
    $library = $libraries['example_external'];

    $this->assertEquals($path . '/css/example_external.css', $library['css'][0]['data']);
    $this->assertEquals('3.14', $library['version']);
  }

  /**
   * Ensures that CSS weights are taken into account properly.
   *
   * @covers ::buildByExtension()
   */
  public function testDefaultCssWeights() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('css_weights')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'css_weights', $path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('css_weights');
    $library = $libraries['example'];
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
   * @covers ::buildByExtension()
   */
  public function testJsWithPositiveWeight() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('js_positive_weight')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'js_positive_weight', $path);

    $this->libraryDiscoveryParser->buildByExtension('js_positive_weight');
  }

  /**
   * Tests a library with CSS/JavaScript and a setting.
   *
   * @covers ::buildByExtension()
   */
  public function testLibraryWithCssJsSetting() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('css_js_settings')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'css_js_settings', $path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('css_js_settings');
    $library = $libraries['example'];

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
   * @covers ::buildByExtension()
   */
  public function testLibraryWithDependencies() {
     $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('dependencies')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'dependencies', $path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('dependencies');
    $library = $libraries['example'];

    $this->assertCount(2, $library['dependencies']);
    $this->assertEquals('external/example_external', $library['dependencies'][0]);
    $this->assertEquals('example_module/example', $library['dependencies'][1]);
  }

  /**
   * Tests a library with a couple of data formats like full URL.
   *
   * @covers ::buildByExtension()
   */
  public function testLibraryWithDataTypes() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('data_types')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'data_types', $path);

    $this->libraryDiscoveryParser->setFileValidUri('public://test.css', TRUE);
    $this->libraryDiscoveryParser->setFileValidUri('public://test2.css', FALSE);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('data_types');
    $library = $libraries['example'];

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
   * Tests a library with JavaScript-specific flags.
   *
   * @covers ::buildByExtension()
   */
  public function testLibraryWithJavaScript() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('js')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'js', $path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('js');
    $library = $libraries['example'];

    $this->assertCount(2, $library['js']);
    $this->assertEquals(FALSE, $library['js'][0]['minified']);
    $this->assertEquals(TRUE, $library['js'][1]['minified']);
  }
  /**
   * Tests that an exception is thrown when license is missing when 3rd party.
   *
   * @expectedException \Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException
   * @expectedExceptionMessage Missing license information in library definition for 'no-license-info-but-remote' in core/tests/Drupal/Tests/Core/Asset/library_test_files/licenses_missing_information.libraries.yml: it has a remote, but no license.
   *
   * @covers ::buildByExtension()
   */
  public function testLibraryThirdPartyWithMissingLicense() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('licenses_missing_information')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'licenses_missing_information', $path);

    $this->libraryDiscoveryParser->buildByExtension('licenses_missing_information');
  }

  /**
   * Tests a library with various licenses, some GPL-compatible, some not.
   *
   * @covers ::buildByExtension()
   */
  public function testLibraryWithLicenses() {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('licenses')
      ->will($this->returnValue(TRUE));

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen(DRUPAL_ROOT) + 1);
    $this->libraryDiscoveryParser->setPaths('module', 'licenses', $path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('licenses');


    // For libraries without license info, the default license is applied.
    $library = $libraries['no-license-info'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $this->assertTrue(isset($library['license']));
    $default_license = array(
      'name' => 'GNU-GPL-2.0-or-later',
      'url' => 'https://drupal.org/licensing/faq',
      'gpl-compatible' => TRUE,
    );
    $this->assertEquals($library['license'], $default_license);

    // GPL2-licensed libraries.
    $library = $libraries['gpl2'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = array(
      'name' => 'gpl2',
      'url' => 'https://url-to-gpl2-license',
      'gpl-compatible' => TRUE,
    );
    $this->assertEquals($library['license'], $expected_license);

    // MIT-licensed libraries.
    $library = $libraries['mit'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = array(
      'name' => 'MIT',
      'url' => 'https://url-to-mit-license',
      'gpl-compatible' => TRUE,
    );
    $this->assertEquals($library['license'], $expected_license);

    // Libraries in the Public Domain.
    $library = $libraries['public-domain'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = array(
      'name' => 'Public Domain',
      'url' => 'https://url-to-public-domain-license',
      'gpl-compatible' => TRUE,
    );
    $this->assertEquals($library['license'], $expected_license);

    // Apache-licensed libraries.
    $library = $libraries['apache'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = array(
      'name' => 'apache',
      'url' => 'https://url-to-apache-license',
      'gpl-compatible' => FALSE,
    );
    $this->assertEquals($library['license'], $expected_license);

    // Copyrighted libraries.
    $library = $libraries['copyright'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = array(
      'name' => '© Some company',
      'gpl-compatible' => FALSE,
    );
    $this->assertEquals($library['license'], $expected_license);
  }

}

/**
 * Wraps the tested class to mock the external dependencies.
 */
class TestLibraryDiscoveryParser extends LibraryDiscoveryParser {

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
