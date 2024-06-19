<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Asset\AssetResolver;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Asset\AssetResolver
 * @group Asset
 */
class AssetResolverTest extends UnitTestCase {

  /**
   * The tested asset resolver service.
   *
   * @var \Drupal\Core\Asset\AssetResolver
   */
  protected $assetResolver;

  /**
   * The mocked library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $libraryDiscovery;

  /**
   * The mocked library dependency resolver.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $libraryDependencyResolver;

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
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * A mocked English language object.
   */
  protected LanguageInterface $english;

  /**
   * A mocked Japanese language object.
   */
  protected LanguageInterface $japanese;
  /**
   * An array of library definitions.
   */
  protected $libraries = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->libraryDiscovery = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscovery')
      ->disableOriginalConstructor()
      ->getMock();
    $this->libraries = [
      'drupal' => [
        'version' => '1.0.0',
        'css' => [],
        'js' =>
        [
          'core/misc/drupal.js' => ['data' => 'core/misc/drupal.js', 'preprocess' => TRUE],
        ],
        'license' => '',
      ],
      'jquery' => [
        'version' => '1.0.0',
        'css' => [],
        'js' =>
        [
          'core/misc/jquery.js' => ['data' => 'core/misc/jquery.js', 'minified' => TRUE],
        ],
        'license' => '',
      ],
      'llama' => [
        'version' => '1.0.0',
        'css' =>
        [
          'core/misc/llama.css' => ['data' => 'core/misc/llama.css'],
        ],
        'js' => [],
        'license' => '',
      ],
      'piggy' => [
        'version' => '1.0.0',
        'css' =>
        [
          'core/misc/piggy.css' => ['data' => 'core/misc/piggy.css'],
        ],
        'js' => [],
        'license' => '',
      ],
    ];
    $this->libraryDependencyResolver = $this->createMock('\Drupal\Core\Asset\LibraryDependencyResolverInterface');
    $this->libraryDependencyResolver->expects($this->any())
      ->method('getLibrariesWithDependencies')
      ->willReturnArgument(0);
    $this->libraryDependencyResolver->expects($this->any())
      ->method('getMinimalRepresentativeSubset')
      ->willReturnArgument(0);
    $this->moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $active_theme = $this->getMockBuilder('\Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager->expects($this->any())
      ->method('getActiveTheme')
      ->willReturn($active_theme);

    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $english = $this->createMock('\Drupal\Core\Language\LanguageInterface');
    $english->expects($this->any())
      ->method('getId')
      ->willReturn('en');
    $this->english = $english;
    $japanese = $this->createMock('\Drupal\Core\Language\LanguageInterface');
    $japanese->expects($this->any())
      ->method('getId')
      ->willReturn('jp');
    $this->japanese = $japanese;
    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($english, $english, $japanese, $japanese);
    $this->cache = new TestMemoryBackend(new Time());

    $this->assetResolver = new AssetResolver($this->libraryDiscovery, $this->libraryDependencyResolver, $this->moduleHandler, $this->themeManager, $this->languageManager, $this->cache);
  }

  /**
   * @covers ::getCssAssets
   * @dataProvider providerAttachedCssAssets
   */
  public function testGetCssAssets(AttachedAssetsInterface $assets_a, AttachedAssetsInterface $assets_b, $expected_css_cache_item_count): void {
    $this->libraryDiscovery->expects($this->any())
      ->method('getLibraryByName')
      ->willReturnOnConsecutiveCalls(
        $this->libraries['drupal'],
        $this->libraries['llama'],
        $this->libraries['llama'],
        $this->libraries['piggy'],
        $this->libraries['piggy'],
      );
    $this->assetResolver->getCssAssets($assets_a, FALSE, $this->english);
    $this->assetResolver->getCssAssets($assets_b, FALSE, $this->english);
    $this->assertCount($expected_css_cache_item_count, $this->cache->getAllCids());
  }

  public static function providerAttachedCssAssets() {
    $time = time();
    return [
      'one js only library and one css only library' => [
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal']),
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['llama/css']),
        1,
      ],
      'two different css libraries' => [
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal', 'llama/css']),
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['piggy/css']),
        2,
      ],
    ];
  }

  /**
   * @covers ::getJsAssets
   * @dataProvider providerAttachedJsAssets
   */
  public function testGetJsAssets(AttachedAssetsInterface $assets_a, AttachedAssetsInterface $assets_b, $expected_js_cache_item_count, $expected_multilingual_js_cache_item_count): void {
    $this->libraryDiscovery->expects($this->any())
      ->method('getLibraryByName')
      ->willReturnOnConsecutiveCalls(
        $this->libraries['drupal'],
        $this->libraries['drupal'],
        $this->libraries['jquery'],
        $this->libraries['drupal'],
        $this->libraries['drupal'],
        $this->libraries['jquery'],
      );
    $this->assetResolver->getJsAssets($assets_a, FALSE, $this->english);
    $this->assetResolver->getJsAssets($assets_b, FALSE, $this->english);
    $this->assertCount($expected_js_cache_item_count, $this->cache->getAllCids());

    $this->assetResolver->getJsAssets($assets_a, FALSE, $this->japanese);
    $this->assetResolver->getJsAssets($assets_b, FALSE, $this->japanese);
    $this->assertCount($expected_multilingual_js_cache_item_count, $this->cache->getAllCids());
  }

  public static function providerAttachedJsAssets() {
    $time = time();
    return [
      'same libraries, different timestamps' => [
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal'])->setSettings(['currentTime' => $time]),
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal'])->setSettings(['currentTime' => $time + 100]),
        1,
        2,
      ],
      'different libraries, same timestamps' => [
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal'])->setSettings(['currentTime' => $time]),
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal', 'core/jquery'])->setSettings(['currentTime' => $time]),
        2,
        3,
      ],
    ];
  }

}

if (!defined('CSS_AGGREGATE_DEFAULT')) {
  define('CSS_AGGREGATE_DEFAULT', 0);
}

if (!defined('JS_DEFAULT')) {
  define('JS_DEFAULT', 0);
}

class TestMemoryBackend extends MemoryBackend {

  public function getAllCids() {
    return array_keys($this->cache);
  }

}
