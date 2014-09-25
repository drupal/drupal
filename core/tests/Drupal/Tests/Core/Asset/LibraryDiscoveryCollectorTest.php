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
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The mock lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $lock;

  /**
   * The mock library discovery parser.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryParser|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $libraryDiscoveryParser;

  /**
   * The library discovery collector under test.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryCollector
   */
  protected $libraryDiscoveryCollector;

  /**
   * Test library data.
   *
   * @var array
   */
  protected $libraryData = array(
    'test_1' => array(
      'js' => array(),
      'css' => array(),
    ),
    'test_2' => array(
      'js' => array(),
      'css' => array(),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->libraryDiscoveryParser = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscoveryParser')
      ->disableOriginalConstructor()
      ->getMock();

    $this->libraryDiscoveryCollector = new LibraryDiscoveryCollector($this->cache, $this->lock, $this->libraryDiscoveryParser);
  }

  /**
   * Tests the resolve cache miss function.
   *
   * @covers ::resolveCacheMiss
   */
  public function testResolveCacheMiss() {
    $this->libraryDiscoveryParser->expects($this->once())
      ->method('buildByExtension')
      ->with('test')
      ->will($this->returnValue($this->libraryData));

    $this->assertSame($this->libraryData, $this->libraryDiscoveryCollector->get('test'));
    $this->assertSame($this->libraryData, $this->libraryDiscoveryCollector->get('test'));
  }

  /**
   * Tests the destruct method.
   *
   * @covers ::destruct
   */
  public function testDestruct() {
    $this->libraryDiscoveryParser->expects($this->once())
      ->method('buildByExtension')
      ->with('test')
      ->will($this->returnValue($this->libraryData));

    $lock_key = 'library_info:Drupal\Core\Cache\CacheCollector';

    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($lock_key)
      ->will($this->returnValue(TRUE));
    $this->cache->expects($this->exactly(2))
      ->method('get')
      ->with('library_info')
      ->will($this->returnValue(FALSE));
    $this->cache->expects($this->once())
      ->method('set')
      ->with('library_info', array('test' => $this->libraryData), Cache::PERMANENT, array('library_info'));
    $this->lock->expects($this->once())
      ->method('release')
      ->with($lock_key);

    // This should get data and persist the key.
    $this->libraryDiscoveryCollector->get('test');
    $this->libraryDiscoveryCollector->destruct();
  }

}
