<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\DatabaseBackendTagTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\simpletest\DrupalUnitTestBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests DatabaseBackend cache tag implementation.
 */
class DatabaseBackendTagTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  public static function getInfo() {
    return array(
      'name' => 'Database backend tag test',
      'description' => 'Tests database backend cache tag implementation.',
      'group' => 'Cache',
    );
  }

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
    $tags = array('test_tag' => array(1, 2, 3));
    $bins = array('data', 'bootstrap', 'render');
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $bin->set('test', 'value', Cache::PERMANENT, $tags);
      $this->assertTrue($bin->get('test'), 'Cache item was set in bin.');
    }

    $invalidations_before = intval(db_select('cache_tags')->fields('cache_tags', array('invalidations'))->condition('tag', 'test_tag:2')->execute()->fetchField());
    Cache::invalidateTags(array('test_tag' => array(2)));

    // Test that cache entry has been invalidated in multiple bins.
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $this->assertFalse($bin->get('test'), 'Tag invalidation affected item in bin.');
    }

    // Test that only one tag invalidation has occurred.
    $invalidations_after = intval(db_select('cache_tags')->fields('cache_tags', array('invalidations'))->condition('tag', 'test_tag:2')->execute()->fetchField());
    $this->assertEqual($invalidations_after, $invalidations_before + 1, 'Only one addition cache tag invalidation has occurred after invalidating a tag used in multiple bins.');
  }

  public function testTagDeletetions() {
    // Create cache entry in multiple bins.
    $tags = array('test_tag' => array(1, 2, 3));
    $bins = array('data', 'bootstrap', 'render');
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $bin->set('test', 'value', Cache::PERMANENT, $tags);
      $this->assertTrue($bin->get('test'), 'Cache item was set in bin.');
    }

    $deletions_before = intval(db_select('cache_tags')->fields('cache_tags', array('deletions'))->condition('tag', 'test_tag:2')->execute()->fetchField());
    Cache::deleteTags(array('test_tag' => array(2)));

    // Test that cache entry has been deleted in multiple bins.
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $this->assertFalse($bin->get('test'), 'Tag invalidation affected item in bin.');
    }

    // Test that only one tag deletion has occurred.
    $deletions_after = intval(db_select('cache_tags')->fields('cache_tags', array('deletions'))->condition('tag', 'test_tag:2')->execute()->fetchField());
    $this->assertEqual($deletions_after, $deletions_before + 1, 'Only one addition cache tag deletion has occurred after deleting a tag used in multiple bins.');
  }

}
