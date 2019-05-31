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
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $libraryDiscovery;

  /**
   * The mocked library dependency resolver.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $libraryDependencyResolver;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeManager;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
   * @group legacy
   *
   * Note the legacy group is used here because
   * ActiveTheme::getStyleSheetsRemove() is called and is deprecated. As this
   * code path will still be triggered until Drupal 9 we have to add the group.
   * We do not trigger a silenced deprecation.
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
        (new AttachedAssets())->setAlreadyLoadedLibraries([])->setLibraries(['core/drupal'])->setSettings(['currenttime' => $time]),
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
