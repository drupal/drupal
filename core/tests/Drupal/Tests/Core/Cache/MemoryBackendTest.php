<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the MemoryBackend cache.
 */
#[CoversClass(MemoryBackend::class)]
#[Group('Cache')]
class MemoryBackendTest extends UnitTestCase {

  /**
   * Tests that expired cache items are removed from memory.
   *
   * @legacy-covers ::garbageCollection
   */
  public function testGarbageCollection(): void {
    $time = $this->createMock(TimeInterface::class);
    $time->expects($this->any())
      ->method('getRequestTime')
      ->willReturn(100);
    $backend = new MemoryBackend($time);

    // Set a cache item that is expired.
    $backend->set('foo', 0, 10);
    // Set a cache item that is not expired.
    $backend->set('bar', 1, 200);
    // Set a permanent cache item.
    $backend->set('baz', 2, Cache::PERMANENT);

    // Verify that the cache entries were set.
    $this->assertInstanceOf(\stdClass::class, $backend->get('foo', TRUE));
    $this->assertInstanceOf(\stdClass::class, $backend->get('bar', TRUE));
    $this->assertInstanceOf(\stdClass::class, $backend->get('baz', TRUE));

    $backend->garbageCollection();

    // Verify that the cache entries were cleared or retained correctly.
    $this->assertFalse($backend->get('foo', TRUE));
    $this->assertInstanceOf(\stdClass::class, $backend->get('bar', TRUE));
    $this->assertInstanceOf(\stdClass::class, $backend->get('baz', TRUE));
  }

}
