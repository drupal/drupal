<?php

namespace Drupal\Core\Render;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Wraps the caching logic for the render caching system.
 *
 * @internal
 */
class RenderCache implements RenderCacheInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The variation cache factory.
   *
   * @var \Drupal\Core\Cache\VariationCacheFactoryInterface
   */
  protected $cacheFactory;

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextsManager;

  /**
   * Constructs a new RenderCache object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Cache\VariationCacheFactoryInterface $cache_factory
   *   The variation cache factory.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager.
   */
  public function __construct(RequestStack $request_stack, $cache_factory, CacheContextsManager $cache_contexts_manager) {
    if ($cache_factory instanceof CacheFactoryInterface) {
      @trigger_error('Injecting ' . __CLASS__ . ' with the "cache_factory" service is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use "variation_cache_factory" instead. See https://www.drupal.org/node/3365546', E_USER_DEPRECATED);
      $cache_factory = \Drupal::service('variation_cache_factory');
    }
    $this->requestStack = $request_stack;
    $this->cacheFactory = $cache_factory;
    $this->cacheContextsManager = $cache_contexts_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get(array $elements) {
    // Form submissions rely on the form being built during the POST request,
    // and render caching of forms prevents this from happening.
    // @todo remove the isMethodCacheable() check when
    //   https://www.drupal.org/node/2367555 lands.
    if (!$this->requestStack->getCurrentRequest()->isMethodCacheable() || !$this->isElementCacheable($elements)) {
      return FALSE;
    }

    $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'render';
    if (($cache_bin = $this->cacheFactory->get($bin)) && $cache = $cache_bin->get($elements['#cache']['keys'], CacheableMetadata::createFromRenderArray($elements))) {
      return $cache->data;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function set(array &$elements, array $pre_bubbling_elements) {
    // Form submissions rely on the form being built during the POST request,
    // and render caching of forms prevents this from happening.
    // @todo remove the isMethodCacheable() check when
    //   https://www.drupal.org/node/2367555 lands.
    if (!$this->requestStack->getCurrentRequest()->isMethodCacheable() || !$this->isElementCacheable($elements)) {
      return FALSE;
    }

    $bin = $elements['#cache']['bin'] ?? 'render';
    $cache_bin = $this->cacheFactory->get($bin);
    $data = $this->getCacheableRenderArray($elements);
    $cache_bin->set(
      $elements['#cache']['keys'],
      $data,
      CacheableMetadata::createFromRenderArray($data)->addCacheTags(['rendered']),
      CacheableMetadata::createFromRenderArray($pre_bubbling_elements)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableRenderArray(array $elements) {
    $data = [
      '#markup' => $elements['#markup'],
      '#attached' => $elements['#attached'],
      '#cache' => [
        'contexts' => $elements['#cache']['contexts'],
        'tags' => $elements['#cache']['tags'],
        'max-age' => $elements['#cache']['max-age'],
      ],
    ];

    // Preserve cacheable items if specified. If we are preserving any cacheable
    // children of the element, we assume we are only interested in their
    // individual markup and not the parent's one, thus we empty it to minimize
    // the cache entry size.
    if (!empty($elements['#cache_properties']) && is_array($elements['#cache_properties'])) {
      $data['#cache_properties'] = $elements['#cache_properties'];

      // Extract all the cacheable items from the element using cache
      // properties.
      $cacheable_items = array_intersect_key($elements, array_flip($elements['#cache_properties']));
      $cacheable_children = Element::children($cacheable_items);
      if ($cacheable_children) {
        $data['#markup'] = '';
        // Cache only cacheable children's markup.
        foreach ($cacheable_children as $key) {
          // We can assume that #markup is safe at this point.
          $cacheable_items[$key] = ['#markup' => Markup::create($cacheable_items[$key]['#markup'])];
        }
      }
      $data += $cacheable_items;
    }

    $data['#markup'] = Markup::create($data['#markup']);
    return $data;
  }

  /**
   * Checks whether a renderable array can be cached.
   *
   * This allows us to not even have to instantiate the cache backend if a
   * renderable array does not have any cache keys or specifies a zero cache
   * max age.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return bool
   *   Whether the renderable array is cacheable.
   */
  protected function isElementCacheable(array $element) {
    // If the maximum age is zero, then caching is effectively prohibited.
    if (isset($element['#cache']['max-age']) && $element['#cache']['max-age'] === 0) {
      return FALSE;
    }
    return isset($element['#cache']['keys']);
  }

}
