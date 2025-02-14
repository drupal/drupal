<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException;
use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Asset\LibraryDiscoveryParser;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Asset\LibraryDiscoveryParser
 * @group Asset
 */
class LibraryDiscoveryParserTest extends UnitTestCase {

  /**
   * The tested library discovery parser service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryParser|\Drupal\Tests\Core\Asset\TestLibraryDiscoveryParser
   */
  protected $libraryDiscoveryParser;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeManager;

  /**
   * The mocked active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $activeTheme;

  /**
   * The mocked lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * The mocked stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $streamWrapperManager;

  /**
   * The mocked libraries directory file finder.
   *
   * @var \Drupal\Core\Asset\LibrariesDirectoryFileFinder|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $librariesDirectoryFileFinder;

  /**
   * The mocked extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $extensionPathResolver;

  /**
   * The mocked extension path resolver.
   *
   * @var \Drupal\Core\Theme\ComponentPluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $componentPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->themeManager = $this->createMock(ThemeManagerInterface::class);
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->activeTheme->expects($this->any())
      ->method('getLibrariesOverride')
      ->willReturn([]);
    $this->themeManager->expects($this->any())
      ->method('getActiveTheme')
      ->willReturn($this->activeTheme);
    $this->streamWrapperManager = $this->createMock(StreamWrapperManagerInterface::class);
    $this->librariesDirectoryFileFinder = $this->createMock(LibrariesDirectoryFileFinder::class);
    $this->extensionPathResolver = $this->createMock(ExtensionPathResolver::class);
    $this->componentPluginManager = $this->createMock(ComponentPluginManager::class);
    $this->libraryDiscoveryParser = new TestLibraryDiscoveryParser($this->root, $this->moduleHandler, $this->themeManager, $this->streamWrapperManager, $this->librariesDirectoryFileFinder, $this->extensionPathResolver, $this->componentPluginManager);
  }

  /**
   * Tests that basic functionality works for getLibraryByName.
   *
   * @covers ::buildByExtension
   *
   * @runInSeparateProcess
   */
  public function testBuildByExtensionSimple(): void {
    FileCacheFactory::setPrefix('testing');
    // Use the default file cache configuration.
    FileCacheFactory::setConfiguration([
      'library_parser' => [],
    ]);
    $this->libraryDiscoveryParser = new TestLibraryDiscoveryParser($this->root, $this->moduleHandler, $this->themeManager, $this->streamWrapperManager, $this->librariesDirectoryFileFinder, $this->extensionPathResolver, $this->componentPluginManager);
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'example_module')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('example_module');
    $library = $libraries['example'];

    $this->assertCount(0, $library['js']);
    $this->assertCount(2, $library['css']);
    $this->assertCount(0, $library['dependencies']);
    $this->assertEquals($path . '/css/example.css', $library['css'][0]['data']);

    // Ensures that VERSION is replaced by the current core version.
    $this->assertEquals(\Drupal::VERSION, $library['version']);

    // Ensure that the expected FileCache entry exists.
    $cache = FileCacheFactory::get('library_parser')->get($path . '/example_module.libraries.yml');
    $this->assertArrayHasKey('example', $cache);
  }

  /**
   * Tests that a theme can be used instead of a module.
   *
   * @covers ::buildByExtension
   */
  public function testBuildByExtensionWithTheme(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_theme')
      ->willReturn(FALSE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('theme', 'example_theme')
      ->willReturn($path);

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
   * @covers ::buildByExtension
   */
  public function testBuildByExtensionWithMissingLibraryFile(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files_not_existing';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'example_module')
      ->willReturn($path);

    $this->assertSame($this->libraryDiscoveryParser->buildByExtension('example_module'), []);
  }

  /**
   * Tests that an exception is thrown when a libraries file couldn't be parsed.
   *
   * @covers ::buildByExtension
   */
  public function testInvalidLibrariesFile(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('invalid_file')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'invalid_file')
      ->willReturn($path);

    $this->expectException(InvalidLibraryFileException::class);
    $this->libraryDiscoveryParser->buildByExtension('invalid_file');
  }

  /**
   * Tests that no exception is thrown when only dependencies are specified.
   *
   * @covers ::buildByExtension
   */
  public function testBuildByExtensionWithOnlyDependencies(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module_only_dependencies')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'example_module_only_dependencies')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('example_module_only_dependencies');
    $this->assertArrayHasKey('example', $libraries);
  }

  /**
   * Tests that an exception is thrown with only the version property specified.
   *
   * @covers ::buildByExtension
   */
  public function testBuildByExtensionWithMissingInformation(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module_missing_information')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'example_module_missing_information')
      ->willReturn($path);

    $this->expectException(IncompleteLibraryDefinitionException::class);
    $this->expectExceptionMessage("Incomplete library definition for definition 'example' in extension 'example_module_missing_information'");
    $this->libraryDiscoveryParser->buildByExtension('example_module_missing_information');
  }

  /**
   * Tests the version property, and how it propagates to the contained assets.
   *
   * @covers ::buildByExtension
   */
  public function testVersion(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('versions')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'versions')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('versions');

    $this->assertArrayNotHasKey('version', $libraries['no_version']);
    $this->assertEquals(-1, $libraries['no_version']['css'][0]['version']);
    $this->assertEquals(-1, $libraries['no_version']['js'][0]['version']);

    $this->assertEquals('9.8.7.6', $libraries['versioned']['version']);
    $this->assertEquals('9.8.7.6', $libraries['versioned']['css'][0]['version']);
    $this->assertEquals('9.8.7.6', $libraries['versioned']['js'][0]['version']);

    $this->assertEquals(\Drupal::VERSION, $libraries['core-versioned']['version']);
    $this->assertEquals(\Drupal::VERSION, $libraries['core-versioned']['css'][0]['version']);
    $this->assertEquals(\Drupal::VERSION, $libraries['core-versioned']['js'][0]['version']);
  }

  /**
   * Tests that the version property of external libraries is handled.
   *
   * @covers ::buildByExtension
   */
  public function testExternalLibraries(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('external')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'external')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('external');
    $library = $libraries['example_external'];

    $this->assertEquals('http://example.com/css/example_external.css', $library['css'][0]['data']);
    $this->assertEquals('http://example.com/example_external.js', $library['js'][0]['data']);
    $this->assertEquals('3.14', $library['version']);
  }

  /**
   * Ensures that CSS weights are taken into account properly.
   *
   * @covers ::buildByExtension
   */
  public function testDefaultCssWeights(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('css_weights')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'css_weights')
      ->willReturn($path);

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
   * @covers ::buildByExtension
   */
  public function testJsWithPositiveWeight(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('js_positive_weight')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'js_positive_weight')
      ->willReturn($path);

    $this->expectException(\UnexpectedValueException::class);
    $this->libraryDiscoveryParser->buildByExtension('js_positive_weight');
  }

  /**
   * Tests a library with CSS/JavaScript and a setting.
   *
   * @covers ::buildByExtension
   */
  public function testLibraryWithCssJsSetting(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('css_js_settings')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'css_js_settings')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('css_js_settings');
    $library = $libraries['example'];

    // Ensures that the group and type are set automatically.
    $this->assertEquals(-100, $library['js'][0]['group']);
    $this->assertEquals('file', $library['js'][0]['type']);
    $this->assertEquals($path . '/js/example.js', $library['js'][0]['data']);

    $this->assertEquals(0, $library['css'][0]['group']);
    $this->assertEquals('file', $library['css'][0]['type']);
    $this->assertEquals($path . '/css/base.css', $library['css'][0]['data']);

    $this->assertEquals(['key' => 'value'], $library['drupalSettings']);
  }

  /**
   * Tests a library with dependencies.
   *
   * @covers ::buildByExtension
   */
  public function testLibraryWithDependencies(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('dependencies')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'dependencies')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('dependencies');
    $library = $libraries['example'];

    $this->assertCount(2, $library['dependencies']);
    $this->assertEquals('external/example_external', $library['dependencies'][0]);
    $this->assertEquals('example_module/example', $library['dependencies'][1]);
  }

  /**
   * Tests a library with a couple of data formats like full URL.
   *
   * @covers ::buildByExtension
   */
  public function testLibraryWithDataTypes(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('data_types')
      ->willReturn(TRUE);
    $this->streamWrapperManager->expects($this->atLeastOnce())
      ->method('isValidUri')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'data_types')
      ->willReturn($path);

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
   * @covers ::buildByExtension
   */
  public function testLibraryWithJavaScript(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('js')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'js')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('js');
    $library = $libraries['example'];

    $this->assertCount(2, $library['js']);
    $this->assertEquals(FALSE, $library['js'][0]['minified']);
    $this->assertEquals(TRUE, $library['js'][1]['minified']);
  }

  /**
   * Tests that an exception is thrown when license is missing when 3rd party.
   *
   * @covers ::buildByExtension
   */
  public function testLibraryThirdPartyWithMissingLicense(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('licenses_missing_information')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'licenses_missing_information')
      ->willReturn($path);

    $this->expectException(LibraryDefinitionMissingLicenseException::class);
    $this->expectExceptionMessage("Missing license information in library definition for definition 'no-license-info-but-remote' extension 'licenses_missing_information': it has a remote, but no license.");
    $this->libraryDiscoveryParser->buildByExtension('licenses_missing_information');
  }

  /**
   * Tests a library with various licenses, some GPL-compatible, some not.
   *
   * @covers ::buildByExtension
   */
  public function testLibraryWithLicenses(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('licenses')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'licenses')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('licenses');

    // For libraries without license info, the default license is applied.
    $library = $libraries['no-license-info'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $this->assertTrue(isset($library['license']));
    $default_license = [
      'name' => 'GPL-2.0-or-later',
      'url' => 'https://www.drupal.org/licensing/faq',
      'gpl-compatible' => TRUE,
    ];
    $this->assertEquals($library['license'], $default_license);

    // GPL2-licensed libraries.
    $library = $libraries['gpl2'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = [
      'name' => 'gpl2',
      'url' => 'https://url-to-gpl2-license',
      'gpl-compatible' => TRUE,
    ];
    $this->assertEquals($library['license'], $expected_license);

    // MIT-licensed libraries.
    $library = $libraries['mit'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = [
      'name' => 'MIT',
      'url' => 'https://url-to-mit-license',
      'gpl-compatible' => TRUE,
    ];
    $this->assertEquals($library['license'], $expected_license);

    // Libraries in the Public Domain.
    $library = $libraries['public-domain'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = [
      'name' => 'Public Domain',
      'url' => 'https://url-to-public-domain-license',
      'gpl-compatible' => TRUE,
    ];
    $this->assertEquals($library['license'], $expected_license);

    // Apache-licensed libraries.
    $library = $libraries['apache'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = [
      'name' => 'apache',
      'url' => 'https://url-to-apache-license',
      'gpl-compatible' => FALSE,
    ];
    $this->assertEquals($library['license'], $expected_license);

    // Copyrighted libraries.
    $library = $libraries['copyright'];
    $this->assertCount(1, $library['css']);
    $this->assertCount(1, $library['js']);
    $expected_license = [
      'name' => '© Some company',
      'gpl-compatible' => FALSE,
    ];
    $this->assertEquals($library['license'], $expected_license);
  }

  /**
   * Tests libraries with overrides.
   *
   * @covers ::applyLibrariesOverride
   */
  public function testLibraryOverride(): void {
    $mock_theme_path = 'mocked_themes/kittens';
    $this->themeManager = $this->createMock(ThemeManagerInterface::class);
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->activeTheme->expects($this->atLeastOnce())
      ->method('getLibrariesOverride')
      ->willReturn([
        $mock_theme_path => [
          'example_module/example' => [
            'css' => [
              'theme' => [
                'css/example.css' => 'css/overridden.css',
                'css/example2.css' => FALSE,
              ],
            ],
          ],
        ],
      ]);
    $this->themeManager->expects($this->any())
      ->method('getActiveTheme')
      ->willReturn($this->activeTheme);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'example_module')
      ->willReturn($path);
    $this->componentPluginManager = $this->createMock(ComponentPluginManager::class);

    $this->libraryDiscoveryParser = new TestLibraryDiscoveryParser($this->root, $this->moduleHandler, $this->themeManager, $this->streamWrapperManager, $this->librariesDirectoryFileFinder, $this->extensionPathResolver, $this->componentPluginManager);

    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_module')
      ->willReturn(TRUE);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('example_module');
    $library = $libraries['example'];

    $this->assertCount(0, $library['js']);
    $this->assertCount(1, $library['css']);
    $this->assertCount(0, $library['dependencies']);
    $this->assertEquals($mock_theme_path . '/css/overridden.css', $library['css'][0]['data']);
  }

  /**
   * Tests deprecated library with an override.
   *
   * @covers ::applyLibrariesOverride
   *
   * @group legacy
   */
  public function testLibraryOverrideDeprecated(): void {
    $this->expectDeprecation('Theme "deprecated" is overriding a deprecated library. The "deprecated/deprecated" asset library is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use another library instead. See https://www.example.com');
    $mock_theme_path = 'mocked_themes/kittens';
    $this->themeManager = $this->createMock(ThemeManagerInterface::class);
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->activeTheme->expects($this->atLeastOnce())
      ->method('getLibrariesOverride')
      ->willReturn([
        $mock_theme_path => [
          'deprecated/deprecated' => [
            'css' => [
              'theme' => [
                'css/example.css' => 'css/overridden.css',
              ],
            ],
          ],
        ],
      ]);
    $this->themeManager->expects($this->any())
      ->method('getActiveTheme')
      ->willReturn($this->activeTheme);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'deprecated')
      ->willReturn($path);
    $this->componentPluginManager = $this->createMock(ComponentPluginManager::class);
    $this->libraryDiscoveryParser = new TestLibraryDiscoveryParser($this->root, $this->moduleHandler, $this->themeManager, $this->streamWrapperManager, $this->librariesDirectoryFileFinder, $this->extensionPathResolver, $this->componentPluginManager);

    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('deprecated')
      ->willReturn(TRUE);

    $this->libraryDiscoveryParser->buildByExtension('deprecated');
  }

  /**
   * Verifies assertions catch invalid CSS declarations.
   *
   * @dataProvider providerTestCssAssert
   */

  /**
   * Verify an assertion fails if CSS declarations have non-existent categories.
   *
   * @param string $extension
   *   The css extension to build.
   * @param string $exception_message
   *   The expected exception message.
   *
   * @dataProvider providerTestCssAssert
   */
  public function testCssAssert($extension, $exception_message): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with($extension)
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', $extension)
      ->willReturn($path);

    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage($exception_message);
    $this->libraryDiscoveryParser->buildByExtension($extension);
  }

  /**
   * Data provider for testing bad CSS declarations.
   */
  public static function providerTestCssAssert() {
    return [
      'css_bad_category' => ['css_bad_category', 'See https://www.drupal.org/node/2274843.'],
      'Improper CSS nesting' => ['css_bad_nesting', 'CSS must be nested under a category. See https://www.drupal.org/node/2274843.'],
      'Improper CSS nesting array' => ['css_bad_nesting_array', 'CSS files should be specified as key/value pairs, where the values are configuration options. See https://www.drupal.org/node/2274843.'],
    ];
  }

  /**
   * @covers ::buildByExtension
   */
  public function testNonCoreLibrariesFound(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_contrib_module')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'example_contrib_module')
      ->willReturn($path);

    $this->librariesDirectoryFileFinder->expects($this->once())
      ->method('find')
      ->with('third_party_library/css/example.css')
      ->willReturn('sites/example.com/libraries/third_party_library/css/example.css');

    $libraries = $this->libraryDiscoveryParser->buildByExtension('example_contrib_module');
    $library = $libraries['third_party_library'];

    $this->assertCount(0, $library['js']);
    $this->assertCount(1, $library['css']);
    $this->assertCount(0, $library['dependencies']);
    // The location is determined by the libraries directory file finder.
    $this->assertEquals('sites/example.com/libraries/third_party_library/css/example.css', $library['css'][0]['data']);
  }

  /**
   * @covers ::buildByExtension
   */
  public function testNonCoreLibrariesNotFound(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('example_contrib_module')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);

    $this->extensionPathResolver->expects($this->once())
      ->method('getPath')
      ->willReturnMap([
        ['module', 'example_contrib_module', $path],
        ['profile', 'library_testing', 'profiles/library_testing'],
      ]);

    $this->librariesDirectoryFileFinder->expects($this->once())
      ->method('find')
      ->with('third_party_library/css/example.css')
      ->willReturn(FALSE);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('example_contrib_module');
    $library = $libraries['third_party_library'];

    $this->assertCount(0, $library['js']);
    $this->assertCount(1, $library['css']);
    $this->assertCount(0, $library['dependencies']);
    // The location will be the same as provided in the library definition even
    // though it does not exist.
    $this->assertEquals('libraries/third_party_library/css/example.css', $library['css'][0]['data']);
  }

  /**
   * @covers ::parseLibraryInfo
   */
  public function testEmptyLibraryFile(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('empty')
      ->willReturn(TRUE);

    $path = __DIR__ . '/library_test_files';
    $path = substr($path, strlen($this->root) + 1);
    $this->extensionPathResolver->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('module', 'empty')
      ->willReturn($path);

    $libraries = $this->libraryDiscoveryParser->buildByExtension('empty');

    $this->assertEquals([], $libraries);
  }

}

/**
 * Wraps the tested class to mock the external dependencies.
 */
class TestLibraryDiscoveryParser extends LibraryDiscoveryParser {

  /**
   * The valid URIs.
   *
   * @var array
   */
  protected $validUris;

  protected function fileValidUri($source) {
    return $this->validUris[$source] ?? FALSE;
  }

  public function setFileValidUri($source, $valid): void {
    $this->validUris[$source] = $valid;
  }

}

if (!defined('CSS_AGGREGATE_DEFAULT')) {
  define('CSS_AGGREGATE_DEFAULT', 0);
}
if (!defined('CSS_AGGREGATE_THEME')) {
  define('CSS_AGGREGATE_THEME', 100);
}
if (!defined('CSS_BASE')) {
  define('CSS_BASE', -200);
}
if (!defined('CSS_LAYOUT')) {
  define('CSS_LAYOUT', -100);
}
if (!defined('CSS_COMPONENT')) {
  define('CSS_COMPONENT', 0);
}
if (!defined('CSS_STATE')) {
  define('CSS_STATE', 100);
}
if (!defined('CSS_THEME')) {
  define('CSS_THEME', 200);
}
if (!defined('JS_SETTING')) {
  define('JS_SETTING', -200);
}
if (!defined('JS_LIBRARY')) {
  define('JS_LIBRARY', -100);
}
if (!defined('JS_DEFAULT')) {
  define('JS_DEFAULT', 0);
}
if (!defined('JS_THEME')) {
  define('JS_THEME', 100);
}
