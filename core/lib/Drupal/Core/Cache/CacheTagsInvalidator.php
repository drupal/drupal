<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Assertion\Inspector;

/**
 * Passes cache tag events to classes that wish to respond to them.
 */
class CacheTagsInvalidator implements CacheTagsInvalidatorInterface, CacheTagsPurgeInterface {

  public function __construct(
    protected iterable $invalidators = [],
    protected iterable $bins = [],
    protected iterable $memoryBins = [],
  ) {}

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
    foreach ([...$this->bins, ...$this->memoryBins] as $bin) {
      if ($bin instanceof CacheTagsInvalidatorInterface) {
        $bin->invalidateTags($tags);
      }
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
   * {@inheritdoc}
   */
  public function purge(): void {
    foreach ($this->invalidators as $invalidator) {
      if ($invalidator instanceof CacheTagsPurgeInterface) {
        $invalidator->purge();
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
    $this->invalidators = [...$this->invalidators, $invalidator];
  }

  /**
   * Adds a cache bin.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $bin
   *   A cache bin.
   */
  public function addBin(CacheBackendInterface $bin): void {
    if ($bin instanceof CacheTagsInvalidatorInterface) {
      $this->bins = [...$this->bins, $bin];
    }
  }

}
