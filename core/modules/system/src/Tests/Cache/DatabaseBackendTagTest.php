<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\DatabaseBackendTagTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\simpletest\KernelTestBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests DatabaseBackend cache tag implementation.
 *
 * @group Cache
 */
class DatabaseBackendTagTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * {@inheritdoc}
   */
  public function containerBuild(ContainerBuilder $container) {
    parent::containerBuild($container);
    // Change container to database cache backends.
    $container
      ->register('cache_factory', 'Drupal\Core\Cache\CacheFactory')
      ->addArgument(new Reference('settings'))
      ->addMethodCall('setContainer', array(new Reference('service_container')));
  }

  public function testTagInvalidations() {
    // Create cache entry in multiple bins.
    $tags = array('test_tag:1', 'test_tag:2', 'test_tag:3');
    $bins = array('data', 'bootstrap', 'render');
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $bin->set('test', 'value', Cache::PERMANENT, $tags);
      $this->assertTrue($bin->get('test'), 'Cache item was set in bin.');
    }

    $invalidations_before = intval(db_select('cachetags')->fields('cachetags', array('invalidations'))->condition('tag', 'test_tag:2')->execute()->fetchField());
    Cache::invalidateTags(array('test_tag:2'));

    // Test that cache entry has been invalidated in multiple bins.
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $this->assertFalse($bin->get('test'), 'Tag invalidation affected item in bin.');
    }

    // Test that only one tag invalidation has occurred.
    $invalidations_after = intval(db_select('cachetags')->fields('cachetags', array('invalidations'))->condition('tag', 'test_tag:2')->execute()->fetchField());
    $this->assertEqual($invalidations_after, $invalidations_before + 1, 'Only one addition cache tag invalidation has occurred after invalidating a tag used in multiple bins.');
  }

  public function testTagDeletions() {
    // Create cache entry in multiple bins.
    $tags = array('test_tag:1', 'test_tag:2', 'test_tag:3');
    $bins = array('data', 'bootstrap', 'render');
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $bin->set('test', 'value', Cache::PERMANENT, $tags);
      $this->assertTrue($bin->get('test'), 'Cache item was set in bin.');
    }

    $deletions_before = intval(db_select('cachetags')->fields('cachetags', array('deletions'))->condition('tag', 'test_tag:2')->execute()->fetchField());
    Cache::deleteTags(array('test_tag:2'));

    // Test that cache entry has been deleted in multiple bins.
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $this->assertFalse($bin->get('test'), 'Tag invalidation affected item in bin.');
    }

    // Test that only one tag deletion has occurred.
    $deletions_after = intval(db_select('cachetags')->fields('cachetags', array('deletions'))->condition('tag', 'test_tag:2')->execute()->fetchField());
    $this->assertEqual($deletions_after, $deletions_before + 1, 'Only one addition cache tag deletion has occurred after deleting a tag used in multiple bins.');
  }

}
