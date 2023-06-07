<?php

namespace Drupal\Core\Cache;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the variation cache factory.
 *
 * @ingroup cache
 */
class VariationCacheFactory implements VariationCacheFactoryInterface {

  /**
   * Instantiated variation cache bins.
   *
   * @var \Drupal\Core\Cache\VariationCacheInterface[]
   */
  protected $bins = [];

  /**
   * Constructs a new VariationCacheFactory object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cacheFactory
   *   The cache factory.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cacheContextsManager
   *   The cache contexts manager.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected CacheFactoryInterface $cacheFactory,
    protected CacheContextsManager $cacheContextsManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    if (!isset($this->bins[$bin])) {
      $this->bins[$bin] = new VariationCache($this->requestStack, $this->cacheFactory->get($bin), $this->cacheContextsManager);
    }
    return $this->bins[$bin];
  }

}
