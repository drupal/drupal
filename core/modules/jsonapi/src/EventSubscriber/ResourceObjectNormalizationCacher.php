<?php

namespace Drupal\jsonapi\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\VariationCacheInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Caches entity normalizations after the response has been sent.
 *
 * @internal
 * @see \Drupal\jsonapi\Normalizer\ResourceObjectNormalizer::getNormalization()
 */
class ResourceObjectNormalizationCacher implements EventSubscriberInterface {

  /**
   * Key for the base subset.
   *
   * The base subset contains the parts of the normalization that are always
   * present. The presence or absence of these are not affected by the requested
   * sparse field sets. This typically includes the resource type name, and the
   * resource ID.
   */
  const RESOURCE_CACHE_SUBSET_BASE = 'base';

  /**
   * Key for the fields subset.
   *
   * The fields subset contains the parts of the normalization that can appear
   * in a normalization based on the selected field set. This subset is
   * incrementally built across different requests for the same resource object.
   * A given field is normalized and put into the cache whenever there is a
   * cache miss for that field.
   */
  const RESOURCE_CACHE_SUBSET_FIELDS = 'fields';

  /**
   * The variation cache.
   *
   * @var \Drupal\Core\Cache\VariationCacheInterface
   */
  protected $variationCache;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The things to cache after the response has been sent.
   *
   * @var array
   */
  protected $toCache = [];

  /**
   * Sets the variation cache.
   *
   * @param \Drupal\Core\Cache\VariationCacheInterface $variation_cache
   *   The variation cache.
   */
  public function setVariationCache(VariationCacheInterface $variation_cache) {
    $this->variationCache = $variation_cache;
  }

  /**
   * Sets the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function setRequestStack(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Reads an entity normalization from cache.
   *
   * The returned normalization may only be a partial normalization because it
   * was previously normalized with a sparse fieldset.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $object
   *   The resource object for which to generate a cache item.
   *
   * @return array|false
   *   The cached normalization parts, or FALSE if not yet cached.
   *
   * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber::renderArrayToResponse()
   */
  public function get(ResourceObject $object) {
    // @todo Investigate whether to cache POST and PATCH requests.
    // @todo Follow up on https://www.drupal.org/project/drupal/issues/3381898.
    if (!$this->requestStack->getCurrentRequest()->isMethodCacheable()) {
      return FALSE;
    }

    $cached = $this->variationCache->get($this->generateCacheKeys($object), new CacheableMetadata());
    return $cached ? $cached->data : FALSE;
  }

  /**
   * Adds a normalization to be cached after the response has been sent.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $object
   *   The resource object for which to generate a cache item.
   * @param array $normalization_parts
   *   The normalization parts to cache.
   */
  public function saveOnTerminate(ResourceObject $object, array $normalization_parts) {
    assert(
      array_keys($normalization_parts) === [
        static::RESOURCE_CACHE_SUBSET_BASE,
        static::RESOURCE_CACHE_SUBSET_FIELDS,
      ]
    );
    $resource_type = $object->getResourceType();
    $key = $resource_type->getTypeName() . ':' . $object->getId();
    $this->toCache[$key] = [$object, $normalization_parts];
  }

  /**
   * Writes normalizations of entities to cache, if any were created.
   *
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *   The Event to process.
   */
  public function onTerminate(TerminateEvent $event) {
    foreach ($this->toCache as $value) {
      [$object, $normalization_parts] = $value;
      $this->set($object, $normalization_parts);
    }
  }

  /**
   * Writes a normalization to cache.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $object
   *   The resource object for which to generate a cache item.
   * @param array $normalization_parts
   *   The normalization parts to cache.
   */
  protected function set(ResourceObject $object, array $normalization_parts) {
    // @todo Investigate whether to cache POST and PATCH requests.
    // @todo Follow up on https://www.drupal.org/project/drupal/issues/3381898.
    if (!$this->requestStack->getCurrentRequest()->isMethodCacheable()) {
      return;
    }

    // Merge the entity's cacheability metadata with that of the normalization
    // parts, so that VariationCache can take care of cache redirects for us.
    $cacheability = CacheableMetadata::createFromObject($object)
      ->merge(static::mergeCacheableDependencies($normalization_parts[static::RESOURCE_CACHE_SUBSET_BASE]))
      ->merge(static::mergeCacheableDependencies($normalization_parts[static::RESOURCE_CACHE_SUBSET_FIELDS]));

    $this->variationCache->set($this->generateCacheKeys($object), $normalization_parts, $cacheability, new CacheableMetadata());
  }

  /**
   * Generates the cache keys for a normalization.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $object
   *   The resource object for which to generate the cache keys.
   *
   * @return string[]
   *   The cache keys to pass to the variation cache.
   *
   * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber::$dynamicPageCacheRedirectRenderArray
   */
  protected static function generateCacheKeys(ResourceObject $object) {
    return [$object->getResourceType()->getTypeName(), $object->getId(), $object->getLanguage()->getId()];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::TERMINATE][] = ['onTerminate'];
    return $events;
  }

  /**
   * Determines the joint cacheability of all provided dependencies.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|object[] $dependencies
   *   The dependencies.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The cacheability of all dependencies.
   *
   * @see \Drupal\Core\Cache\RefinableCacheableDependencyInterface::addCacheableDependency()
   */
  protected static function mergeCacheableDependencies(array $dependencies) {
    $merged_cacheability = new CacheableMetadata();
    array_walk($dependencies, function ($dependency) use ($merged_cacheability) {
      $merged_cacheability->addCacheableDependency($dependency);
    });
    return $merged_cacheability;
  }

}
