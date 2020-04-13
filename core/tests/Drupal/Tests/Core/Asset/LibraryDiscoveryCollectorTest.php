<?php

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\LibraryDiscoveryCollector;
use Drupal\Core\Cache\Cache;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Asset\LibraryDiscoveryCollector
 * @group Asset
 */
class LibraryDiscoveryCollectorTest extends UnitTestCase {

  /**
   * The mock cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mock lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * The mock library discovery parser.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryParser|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $libraryDiscoveryParser;

  /**
   * The library discovery collector under test.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryCollector
   */
  protected $libraryDiscoveryCollector;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeManager;

  /**
   * Test library data.
   *
   * @var array
   */
  protected $libraryData = [
    'test_1' => [
      'js' => [],
      'css' => [],
    ],
    'test_2' => [
      'js' => [],
      'css' => [],
    ],
  ];

  protected $activeTheme;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $this->themeManager = $this->getMockBuilder('Drupal\Core\Theme\ThemeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->libraryDiscoveryParser = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscoveryParser')
      ->disableOriginalConstructor()
      ->getMock();

  }

  /**
   * Tests the resolve cache miss function.
   *
   * @covers ::resolveCacheMiss
   */
  public function testResolveCacheMiss() {
    $this->activeTheme = $this->getMockBuilder('Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager->expects($this->exactly(3))
      ->method('getActiveTheme')
      ->willReturn($this->activeTheme);
    $this->activeTheme->expects($this->once())
      ->method('getName')
      ->willReturn('kitten_theme');
    $this->libraryDiscoveryCollector = new LibraryDiscoveryCollector($this->cache, $this->lock, $this->libraryDiscoveryParser, $this->themeManager);

    $this->libraryDiscoveryParser->expects($this->once())
      ->method('buildByExtension')
      ->with('test')
      ->willReturn($this->libraryData);

    $this->assertSame($this->libraryData, $this->libraryDiscoveryCollector->get('test'));
    $this->assertSame($this->libraryData, $this->libraryDiscoveryCollector->get('test'));
  }

  /**
   * Tests the destruct method.
   *
   * @covers ::destruct
   */
  public function testDestruct() {
    $this->activeTheme = $this->getMockBuilder('Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager->expects($this->exactly(3))
      ->method('getActiveTheme')
      ->willReturn($this->activeTheme);
    $this->activeTheme->expects($this->once())
      ->method('getName')
      ->willReturn('kitten_theme');
    $this->libraryDiscoveryCollector = new LibraryDiscoveryCollector($this->cache, $this->lock, $this->libraryDiscoveryParser, $this->themeManager);

    $this->libraryDiscoveryParser->expects($this->once())
      ->method('buildByExtension')
      ->with('test')
      ->willReturn($this->libraryData);

    $lock_key = 'library_info:kitten_theme:Drupal\Core\Cache\CacheCollector';

    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($lock_key)
      ->will($this->returnValue(TRUE));
    $this->cache->expects($this->exactly(2))
      ->method('get')
      ->with('library_info:kitten_theme')
      ->willReturn(FALSE);
    $this->cache->expects($this->once())
      ->method('set')
      ->with('library_info:kitten_theme', ['test' => $this->libraryData], Cache::PERMANENT, ['library_info']);
    $this->lock->expects($this->once())
      ->method('release')
      ->with($lock_key);

    // This should get data and persist the key.
    $this->libraryDiscoveryCollector->get('test');
    $this->libraryDiscoveryCollector->destruct();
  }

}
