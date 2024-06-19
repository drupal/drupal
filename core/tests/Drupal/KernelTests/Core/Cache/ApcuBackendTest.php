<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\ApcuBackend;

/**
 * Tests the APCu cache backend.
 *
 * @group Cache
 * @requires extension apcu
 */
class ApcuBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createCacheBackend($bin) {
    return new ApcuBackend($bin, $this->databasePrefix, \Drupal::service('cache_tags.invalidator.checksum'), \Drupal::service(TimeInterface::class));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    foreach ($this->cacheBackends as $bin => $cache_backend) {
      $this->cacheBackends[$bin]->removeBin();
    }
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  public function testSetGet(): void {
    parent::testSetGet();

    // Make sure entries are permanent (i.e. no TTL).
    $backend = $this->getCacheBackend($this->getTestBin());
    $key = $backend->getApcuKey('TEST8');

    $iterator = new \APCUIterator('/^' . $key . '/');
    foreach ($iterator as $item) {
      $this->assertEquals(0, $item['ttl']);
      $found = TRUE;
    }
    $this->assertTrue($found);
  }

}
