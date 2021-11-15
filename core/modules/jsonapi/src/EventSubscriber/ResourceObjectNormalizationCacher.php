<?php

namespace Drupal\jsonapi\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Caches entity normalizations after the response has been sent.
 *
 * @internal
 * @see \Drupal\jsonapi\Normalizer\ResourceObjectNormalizer::getNormalization()
 * @todo Refactor once https://www.drupal.org/node/2551419 lands.
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
   * The render cache.
   *
   * @var \Drupal\Core\Render\RenderCacheInterface
   */
  protected $renderCache;

  /**
   * The things to cache after the response has been sent.
   *
   * @var array
   */
  protected $toCache = [];

  /**
   * Sets the render cache service.
   *
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache.
   */
  public function setRenderCache(RenderCacheInterface $render_cache) {
    $this->renderCache = $render_cache;
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
    $cached = $this->renderCache->get(static::generateLookupRenderArray($object));
    return $cached ? $cached['#data'] : FALSE;
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
   *
   * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber::responseToRenderArray()
   * @todo Refactor/remove once https://www.drupal.org/node/2551419 lands.
   */
  protected function set(ResourceObject $object, array $normalization_parts) {
    $base = static::generateLookupRenderArray($object);
    $data_as_render_array = $base + [
      // The data we actually care about.
      '#data' => $normalization_parts,
      // Tell RenderCache to cache the #data property: the data we actually care
      // about.
      '#cache_properties' => ['#data'],
      // These exist only to fulfill the requirements of the RenderCache, which
      // is designed to work with render arrays only. We don't care about these.
      '#markup' => '',
      '#attached' => '',
    ];

    // Merge the entity's cacheability metadata with that of the normalization
    // parts, so that RenderCache can take care of cache redirects for us.
    CacheableMetadata::createFromObject($object)
      ->merge(static::mergeCacheableDependencies($normalization_parts[static::RESOURCE_CACHE_SUBSET_BASE]))
      ->merge(static::mergeCacheableDependencies($normalization_parts[static::RESOURCE_CACHE_SUBSET_FIELDS]))
      ->applyTo($data_as_render_array);

    $this->renderCache->set($data_as_render_array, $base);
  }

  /**
   * Generates a lookup render array for a normalization.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $object
   *   The resource object for which to generate a cache item.
   *
   * @return array
   *   A render array for use with the RenderCache service.
   *
   * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber::$dynamicPageCacheRedirectRenderArray
   */
  protected static function generateLookupRenderArray(ResourceObject $object) {
    return [
      '#cache' => [
        'keys' => [$object->getResourceType()->getTypeName(), $object->getId()],
        'bin' => 'jsonapi_normalizations',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
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
