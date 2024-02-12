<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Assertion\Inspector;

/**
 * Passes cache tag events to classes that wish to respond to them.
 */
class CacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * Holds an array of cache tags invalidators.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface[]
   */
  protected $invalidators = [];

  /**
   * Holds an array of cache bins that support invalidations.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface[]
   */
  protected array $bins = [];

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    assert(Inspector::assertAllStrings($tags), 'Cache tags must be strings.');

    // Notify all added cache tags invalidators.
    foreach ($this->invalidators as $invalidator) {
      $invalidator->invalidateTags($tags);
    }

    // Additionally, notify each cache bin if it implements the service.
    foreach ($this->bins as $bin) {
      $bin->invalidateTags($tags);
    }
  }

  /**
   * Reset statically cached tags in all cache tag checksum services.
   *
   * This is only used by tests.
   */
  public function resetChecksums() {
    foreach ($this->invalidators as $invalidator) {
      if ($invalidator instanceof CacheTagsChecksumInterface) {
        $invalidator->reset();
      }
    }
  }

  /**
   * Adds a cache tags invalidator.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   *   A cache invalidator.
   */
  public function addInvalidator(CacheTagsInvalidatorInterface $invalidator) {
    $this->invalidators[] = $invalidator;
  }

  /**
   * Adds a cache bin.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $bin
   *   A cache bin.
   */
  public function addBin(CacheBackendInterface $bin): void {
    if ($bin instanceof CacheTagsInvalidatorInterface) {
      $this->bins[] = $bin;
    }
  }

}
