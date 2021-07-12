<?php

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\LibraryDiscoveryCollector;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Theme\ActiveTheme;
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
    'test_3' => [
      'js' => [],
      'css' => [
        'theme' => [
          'foo.css' => [],
        ],
      ],
    ],
    'test_4' => [
      'js' => [],
      'css' => [
        'theme' => [
          'bar.css' => [],
        ],
      ],
      'deprecated' => 'The "%library_id%" asset library is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use the test_3 library instead. See https://www.example.com',
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
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager->expects($this->exactly(5))
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
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager->expects($this->exactly(5))
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

  /**
   * Tests library with an extend.
   *
   * @covers ::applyLibrariesExtend
   */
  public function testLibrariesExtend() {
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager->expects($this->any())
      ->method('getActiveTheme')
      ->willReturn($this->activeTheme);
    $this->activeTheme->expects($this->once())
      ->method('getName')
      ->willReturn('kitten_theme');
    $this->activeTheme->expects($this->atLeastOnce())
      ->method('getLibrariesExtend')
      ->willReturn([
        'test/test_3' => [
          'kitten_theme/extend',
        ],
      ]);
    $this->libraryDiscoveryParser->expects($this->exactly(2))
      ->method('buildByExtension')
      ->willReturnMap([
        ['test', $this->libraryData],
        [
          'kitten_theme', [
            'extend' => [
              'css' => [
                'theme' => [
                  'baz.css' => [],
                ],
              ],
            ],
          ],
        ],
      ]);
    $library_discovery_collector = new LibraryDiscoveryCollector($this->cache, $this->lock, $this->libraryDiscoveryParser, $this->themeManager);
    $libraries = $library_discovery_collector->get('test');
    $this->assertSame(['foo.css', 'baz.css'], array_keys($libraries['test_3']['css']['theme']));
  }

  /**
   * Tests a deprecated library with an extend.
   *
   * @covers ::applyLibrariesExtend
   *
   * @group legacy
   */
  public function testLibrariesExtendDeprecated() {
    $this->expectDeprecation('Theme "test" is extending a deprecated library. The "test/test_4" asset library is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use the test_3 library instead. See https://www.example.com');
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager->expects($this->any())
      ->method('getActiveTheme')
      ->willReturn($this->activeTheme);
    $this->activeTheme->expects($this->once())
      ->method('getName')
      ->willReturn('kitten_theme');
    $this->activeTheme->expects($this->atLeastOnce())
      ->method('getLibrariesExtend')
      ->willReturn([
        'test/test_4' => [
          'kitten_theme/extend',
        ],
      ]);
    $this->libraryDiscoveryParser->expects($this->exactly(2))
      ->method('buildByExtension')
      ->willReturnMap([
        ['test', $this->libraryData],
        [
          'kitten_theme', [
            'extend' => [
              'css' => [
                'theme' => [
                  'baz.css' => [],
                ],
              ],
            ],
          ],
        ],
      ]);
    $library_discovery_collector = new LibraryDiscoveryCollector($this->cache, $this->lock, $this->libraryDiscoveryParser, $this->themeManager);
    $library_discovery_collector->get('test');
  }

}
