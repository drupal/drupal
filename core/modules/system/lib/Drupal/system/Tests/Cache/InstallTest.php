<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\InstallTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Cache\InstallBackend;

/**
 * Tests the behavior of the cache backend used for installing Drupal.
 */
class InstallTest extends CacheTestBase {
  protected $profile = 'testing';

  public static function getInfo() {
    return array(
      'name' => 'Cache install test',
      'description' => 'Confirm that the cache backend used for installing Drupal works correctly.',
      'group' => 'Cache',
    );
  }

  function setUp() {
    parent::setUp(array('cache_test'));
  }

  /**
   * Tests the behavior of the cache backend used for installing Drupal.
   *
   * While Drupal is being installed, the cache system must deal with the fact
   * that the database is not initially available, and, after it is available,
   * the fact that other requests that take place while Drupal is being
   * installed (for example, Ajax requests triggered via the installer's user
   * interface) may cache data in the database, which needs to be cleared when
   * the installer makes changes that would result in it becoming stale.
   *
   * We cannot test this process directly, so instead we test it by switching
   * between the normal database cache (Drupal\Core\Cache\DatabaseBackend) and
   * the installer cache (Drupal\Core\Cache\InstallBackend) while setting and
   * clearing various items in the cache.
   */
  function testCacheInstall() {
    $database_cache = new DatabaseBackend('test');
    $install_cache = new InstallBackend('test');

    // Store an item in the database cache, and confirm that the installer's
    // cache backend recognizes that the cache is not empty.
    $database_cache->set('cache_one', 'One');
    $this->assertFalse($install_cache->isEmpty());
    $database_cache->delete('cache_one');
    $this->assertTrue($install_cache->isEmpty());

    // Store an item in the database cache, then use the installer's cache
    // backend to delete it. Afterwards, confirm that it is no longer in the
    // database cache.
    $database_cache->set('cache_one', 'One');
    $this->assertEqual($database_cache->get('cache_one')->data, 'One');
    $install_cache->delete('cache_one');
    $this->assertFalse($database_cache->get('cache_one'));

    // Store multiple items in the database cache, then use the installer's
    // cache backend to delete them. Afterwards, confirm that they are no
    // longer in the database cache.
    $database_cache->set('cache_one', 'One');
    $database_cache->set('cache_two', 'Two');
    $this->assertEqual($database_cache->get('cache_one')->data, 'One');
    $this->assertEqual($database_cache->get('cache_two')->data, 'Two');
    $install_cache->deleteMultiple(array('cache_one', 'cache_two'));
    $this->assertFalse($database_cache->get('cache_one'));
    $this->assertFalse($database_cache->get('cache_two'));

    // Store multiple items in the database cache, then use the installer's
    // cache backend to delete them via a wildcard prefix. Afterwards, confirm
    // that they are no longer in the database cache.
    $database_cache->set('cache_one', 'One');
    $database_cache->set('cache_two', 'Two');
    $this->assertEqual($database_cache->get('cache_one')->data, 'One');
    $this->assertEqual($database_cache->get('cache_two')->data, 'Two');
    $install_cache->deletePrefix('cache_');
    $this->assertFalse($database_cache->get('cache_one'));
    $this->assertFalse($database_cache->get('cache_two'));

    // Store multiple items in the database cache, then use the installer's
    // cache backend to flush the cache. Afterwards, confirm that they are no
    // longer in the database cache.
    $database_cache->set('cache_one', 'One');
    $database_cache->set('cache_two', 'Two');
    $this->assertEqual($database_cache->get('cache_one')->data, 'One');
    $this->assertEqual($database_cache->get('cache_two')->data, 'Two');
    $install_cache->flush();
    $this->assertFalse($database_cache->get('cache_one'));
    $this->assertFalse($database_cache->get('cache_two'));

    // Invalidate a tag using the installer cache, then check that the
    // invalidation was recorded correctly in the database.
    $install_cache->invalidateTags(array('tag'));
    $invalidations = db_query("SELECT invalidations FROM {cache_tags} WHERE tag = 'tag'")->fetchField();
    $this->assertEqual($invalidations, 1);

    // For each cache clearing event that we tried above, try it again after
    // dropping the {cache_test} table. This simulates the early stages of the
    // installer (when the database cache tables won't be available yet) and
    // thereby confirms that the installer's cache backend does not produce
    // errors if the installer ever calls any code early on that tries to clear
    // items from the cache.
    db_drop_table('cache_test');
    try {
      $install_cache->isEmpty();
      $install_cache->delete('cache_one');
      $install_cache->deleteMultiple(array('cache_one', 'cache_two'));
      $install_cache->deletePrefix('cache_');
      $install_cache->flush();
      $install_cache->expire();
      $install_cache->garbageCollection();
      $install_cache->invalidateTags(array('tag'));
      $this->pass("The installer's cache backend can be used even when the cache database tables are unavailable.");
    }
    catch (Exception $e) {
      $this->fail("The installer's cache backend can be used even when the cache database tables are unavailable.");
    }
  }
}
