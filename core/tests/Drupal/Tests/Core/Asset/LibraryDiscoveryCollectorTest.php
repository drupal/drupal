<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\LibraryDiscoveryCollector;
use Drupal\Core\Asset\LibraryDiscoveryParser;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests Drupal\Core\Asset\LibraryDiscoveryCollector.
 */
#[CoversClass(LibraryDiscoveryCollector::class)]
#[Group('Asset')]
class LibraryDiscoveryCollectorTest extends UnitTestCase {

  /**
   * The cache backend.
   */
  protected CacheBackendInterface&Stub $cache;

  /**
   * The lock backend.
   */
  protected LockBackendInterface&Stub $lock;

  /**
   * The mock library discovery parser.
   */
  protected LibraryDiscoveryParser&MockObject $libraryDiscoveryParser;

  /**
   * The library discovery collector under test.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryCollector
   */
  protected $libraryDiscoveryCollector;

  /**
   * The theme manager.
   */
  protected ThemeManagerInterface&Stub $themeManager;

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

  /**
   * The active theme.
   */
  protected ActiveTheme&MockObject $activeTheme;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->createStub(CacheBackendInterface::class);
    $this->lock = $this->createStub(LockBackendInterface::class);
    $this->themeManager = $this->createStub(ThemeManagerInterface::class);
    $this->libraryDiscoveryParser = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscoveryParser')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests the resolve cache miss function.
   */
  public function testResolveCacheMiss(): void {
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager = $this->createMock(ThemeManagerInterface::class);
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
   */
  public function testDestruct(): void {
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->lock = $this->createMock(LockBackendInterface::class);
    $this->themeManager = $this->createMock(ThemeManagerInterface::class);
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
      ->willReturn(TRUE);
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
   * @see ::applyLibrariesExtend
   */
  public function testLibrariesExtend(): void {
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager
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
   * @see ::applyLibrariesExtend
   */
  #[IgnoreDeprecations]
  public function testLibrariesExtendDeprecated(): void {
    $this->expectUserDeprecationMessage('Theme "kitten_theme" is extending a deprecated library. The "test/test_4" asset library is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use the test_3 library instead. See https://www.example.com');
    $this->activeTheme = $this->getMockBuilder(ActiveTheme::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->themeManager
      ->method('getActiveTheme')
      ->willReturn($this->activeTheme);
    $this->activeTheme->expects($this->atLeastOnce())
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
