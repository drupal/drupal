<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheTagsInvalidator.
 */

namespace Drupal\Core\Cache;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Passes cache tag events to classes that wish to respond to them.
 */
class CacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  use ContainerAwareTrait;

  /**
   * Holds an array of cache tags invalidators.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface[]
   */
  protected $invalidators = array();

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    assert('Drupal\Component\Assertion\Inspector::assertAllStrings($tags)', 'Cache tags must be strings.');

    // Notify all added cache tags invalidators.
    foreach ($this->invalidators as $invalidator) {
      $invalidator->invalidateTags($tags);
    }

    // Additionally, notify each cache bin if it implements the service.
    foreach ($this->getInvalidatorCacheBins() as $bin) {
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
   * Returns all cache bins that need to be notified about invalidations.
   *
   * @return \Drupal\Core\Cache\CacheTagsInvalidatorInterface[]
   *   An array of cache backend objects that implement the invalidator
   *   interface, keyed by their cache bin.
   */
  protected function getInvalidatorCacheBins() {
    $bins = array();
    foreach ($this->container->getParameter('cache_bins') as $service_id => $bin) {
      $service = $this->container->get($service_id);
      if ($service instanceof CacheTagsInvalidatorInterface) {
        $bins[$bin] = $service;
      }
    }
    return $bins;
  }

}
