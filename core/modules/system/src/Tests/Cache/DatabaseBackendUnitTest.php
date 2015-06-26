<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\DatabaseBackendUnitTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\DatabaseBackend;

/**
 * Unit test of the database backend using the generic cache unit test base.
 *
 * @group Cache
 */
class DatabaseBackendUnitTest extends GenericCacheBackendUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * Creates a new instance of DatabaseBackend.
   *
   * @return
   *   A new DatabaseBackend object.
   */
  protected function createCacheBackend($bin) {
    return new DatabaseBackend($this->container->get('database'), $this->container->get('cache_tags.invalidator.checksum'), $bin);
  }

  /**
   * {@inheritdoc}
   */
  public function testSetGet() {
    parent::testSetGet();
    $backend = $this->getCacheBackend();

    // Set up a cache ID that is not ASCII and longer than 255 characters so we
    // can test cache ID normalization.
    $cid_long = str_repeat('愛€', 500);
    $cached_value_long = $this->randomMachineName();
    $backend->set($cid_long, $cached_value_long);
    $this->assertIdentical($cached_value_long, $backend->get($cid_long)->data, "Backend contains the correct value for long, non-ASCII cache id.");

    $cid_short = '愛1€';
    $cached_value_short = $this->randomMachineName();
    $backend->set($cid_short, $cached_value_short);
    $this->assertIdentical($cached_value_short, $backend->get($cid_short)->data, "Backend contains the correct value for short, non-ASCII cache id.");
  }

}
