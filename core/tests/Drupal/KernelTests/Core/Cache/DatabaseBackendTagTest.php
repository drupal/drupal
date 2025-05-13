<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsPurgeInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests DatabaseBackend cache tag implementation.
 *
 * @group Cache
 */
class DatabaseBackendTagTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    // Change container to database cache backends.
    $container
      ->register('cache_factory', 'Drupal\Core\Cache\CacheFactory')
      ->addArgument(new Reference('settings'))
      ->addMethodCall('setContainer', [new Reference('service_container')]);
  }

  /**
   * Test tag invalidation.
   */
  public function testTagInvalidations(): void {
    // Create cache entry in multiple bins.
    $tags = ['test_tag:1', 'test_tag:2', 'test_tag:3'];
    $bins = ['data', 'bootstrap', 'render'];
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $bin->set('test', 'value', Cache::PERMANENT, $tags);
      $this->assertNotEmpty($bin->get('test'), 'Cache item was set in bin.');
    }

    $connection = Database::getConnection();
    $invalidations_before = intval($connection->select('cachetags')->fields('cachetags', ['invalidations'])->condition('tag', 'test_tag:2')->execute()->fetchField());
    Cache::invalidateTags(['test_tag:2']);

    // Test that cache entry has been invalidated in multiple bins.
    foreach ($bins as $bin) {
      $bin = \Drupal::cache($bin);
      $this->assertFalse($bin->get('test'), 'Tag invalidation affected item in bin.');
    }

    // Test that only one tag invalidation has occurred.
    $invalidations_after = intval($connection->select('cachetags')->fields('cachetags', ['invalidations'])->condition('tag', 'test_tag:2')->execute()->fetchField());
    $this->assertEquals($invalidations_before + 1, $invalidations_after, 'Only one addition cache tag invalidation has occurred after invalidating a tag used in multiple bins.');
  }

  /**
   * Test cache tag purging.
   */
  public function testTagsPurge(): void {
    $tags = ['test_tag:1', 'test_tag:2', 'test_tag:3'];
    /** @var \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_invalidator */
    $checksum_invalidator = \Drupal::service('cache_tags.invalidator.checksum');
    // Assert that initial current tag checksum is 0. This also ensures that the
    // 'cachetags' table is created, which at this point does not exist yet.
    $this->assertEquals(0, $checksum_invalidator->getCurrentChecksum($tags));

    /** @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator */
    $invalidator = \Drupal::service('cache_tags.invalidator');
    $invalidator->invalidateTags($tags);
    // Checksum should be incremented by 1 by the invalidation for each tag.
    $this->assertEquals(3, $checksum_invalidator->getCurrentChecksum($tags));

    // After purging, confirm checksum is 0 and the 'cachetags' table is empty.
    $this->assertInstanceOf(CacheTagsPurgeInterface::class, $invalidator);
    $invalidator->purge();
    $this->assertEquals(0, $checksum_invalidator->getCurrentChecksum($tags));

    $rows = Database::getConnection()->select('cachetags')
      ->fields('cachetags')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEmpty($rows, 'cachetags table is empty.');
  }

}
