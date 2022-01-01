<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Asset\AssetResolverTest.
 */

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\AssetResolver;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Cache\MemoryBackend;
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->libraryDiscovery = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscovery')
      ->disableOriginalConstructor()
      ->getMock();
    $this->libraryDependencyResolver = $this->createMock('\Drupal\Core\Asset\LibraryDependencyResolverInterface');
    $this->libraryDependencyResolver->expects($this->any())
      ->method('getLibrariesWithDependencies')
      ->willReturnArgument(0);
    $this->moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $active_theme = $this->getMockBuilder('\Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $active_theme->expects($this->any())
      ->method('getName')
      ->willReturn('bartik');
    $this->themeManager->expects($this->any())
      ->method('getActiveTheme')
      ->willReturn($active_theme);

    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $english = $this->createMock('\Drupal\Core\Language\LanguageInterface');
    $english->expects($this->any())
      ->method('getId')
      ->willReturn('en');
    $japanese = $this->createMock('\Drupal\Core\Language\LanguageInterface');
    $japanese->expects($this->any())
      ->method('getId')
      ->willReturn('jp');
    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->onConsecutiveCalls($english, $english, $japanese, $japanese));
    $this->cache = new TestMemoryBackend();

    $this->assetResolver = new AssetResolver($this->libraryDiscovery, $this->libraryDependencyResolver, $this->moduleHandler, $this->themeManager, $this->languageManager, $this->cache);
  }

  /**
   * @covers ::getCssAssets
   * @dataProvider providerAttachedAssets
   */
  public function testGetCssAssets(AttachedAssetsInterface $assets_a, AttachedAssetsInterface $assets_b, $expected_cache_item_count) {
    $this->assetResolver->getCssAssets($assets_a, FALSE);
    $this->assetResolver->getCssAssets($assets_b, FALSE);
    $this->assertCount($expected_cache_item_count, $this->cache->getAllCids());
  }

  /**
   * @covers ::getJsAssets
   * @dataProvider providerAttachedAssets
   */
  public function testGetJsAssets(AttachedAssetsInterface $assets_a, AttachedAssetsInterface $assets_b, $expected_cache_item_count) {
    $this->assetResolver->getJsAssets($assets_a, FALSE);
    $this->assetResolver->getJsAssets($assets_b, FALSE);
    $this->assertCount($expected_cache_item_count, $this->cache->getAllCids());

    $this->assetResolver->getJsAssets($assets_a, FALSE);
    $this->assetResolver->getJsAssets($assets_b, FALSE);
    $this->assertCount($expected_cache_item_count * 2, $this->cache->getAllCids());
  }

  public function providerAttachedAssets() {
    $time = time();
    return [
      'same libraries, different timestamps' => [
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal'])->setSettings(['currentTime' => $time]),
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal'])->setSettings(['currentTime' => $time + 100]),
        1,
      ],
      'different libraries, same timestamps' => [
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal'])->setSettings(['currentTime' => $time]),
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal', 'core/jquery'])->setSettings(['currentTime' => $time]),
        2,
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
