<?php

/**
 * @file
 * Contains \Drupal\Core\Render\RenderCache.
 */

namespace Drupal\Core\Render;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Cache\CacheFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Wraps the caching logic for the render caching system.
 */
class RenderCache implements RenderCacheInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The cache factory.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
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
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cache_factory
   *   The cache factory.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager.
   */
  public function __construct(RequestStack $request_stack, CacheFactoryInterface $cache_factory, CacheContextsManager $cache_contexts_manager) {
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
    // @todo remove the isMethodSafe() check when
    //       https://www.drupal.org/node/2367555 lands.
    if (!$this->requestStack->getCurrentRequest()->isMethodSafe() || !$cid = $this->createCacheID($elements)) {
      return FALSE;
    }
    $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'render';

    if (!empty($cid) && ($cache_bin = $this->cacheFactory->get($bin)) && $cache = $cache_bin->get($cid)) {
      $cached_element = $cache->data;
      // Two-tier caching: redirect to actual (post-bubbling) cache item.
      // @see \Drupal\Core\Render\RendererInterface::render()
      // @see ::set()
      if (isset($cached_element['#cache_redirect'])) {
        return $this->get($cached_element);
      }
      // Return the cached element.
      return $cached_element;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function set(array &$elements, array $pre_bubbling_elements) {
    // Form submissions rely on the form being built during the POST request,
    // and render caching of forms prevents this from happening.
    // @todo remove the isMethodSafe() check when
    //       https://www.drupal.org/node/2367555 lands.
    if (!$this->requestStack->getCurrentRequest()->isMethodSafe() || !$cid = $this->createCacheID($elements)) {
      return FALSE;
    }

    $data = $this->getCacheableRenderArray($elements);

    $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'render';
    $expire = ($elements['#cache']['max-age'] === Cache::PERMANENT) ? Cache::PERMANENT : (int) $this->requestStack->getMasterRequest()->server->get('REQUEST_TIME') + $elements['#cache']['max-age'];
    $cache = $this->cacheFactory->get($bin);

    // Calculate the pre-bubbling CID.
    $pre_bubbling_cid = $this->createCacheID($pre_bubbling_elements);

    // Two-tier caching: detect different CID post-bubbling, create redirect,
    // update redirect if different set of cache contexts.
    // @see \Drupal\Core\Render\RendererInterface::render()
    // @see ::get()
    if ($pre_bubbling_cid && $pre_bubbling_cid !== $cid) {
      // The cache redirection strategy we're implementing here is pretty
      // simple in concept. Suppose we have the following render structure:
      // - A (pre-bubbling, specifies #cache['keys'] = ['foo'])
      // -- B (specifies #cache['contexts'] = ['b'])
      //
      // At the time that we're evaluating whether A's rendering can be
      // retrieved from cache, we won't know the contexts required by its
      // children (the children might not even be built yet), so cacheGet()
      // will only be able to get what is cached for a $cid of 'foo'. But at
      // the time we're writing to that cache, we do know all the contexts that
      // were specified by all children, so what we need is a way to
      // persist that information between the cache write and the next cache
      // read. So, what we can do is store the following into 'foo':
      // [
      //   '#cache_redirect' => TRUE,
      //   '#cache' => [
      //     ...
      //     'contexts' => ['b'],
      //   ],
      // ]
      //
      // This efficiently lets cacheGet() redirect to a $cid that includes all
      // of the required contexts. The strategy is on-demand: in the case where
      // there aren't any additional contexts required by children that aren't
      // already included in the parent's pre-bubbled #cache information, no
      // cache redirection is needed.
      //
      // When implementing this redirection strategy, special care is needed to
      // resolve potential cache ping-pong problems. For example, consider the
      // following render structure:
      // - A (pre-bubbling, specifies #cache['keys'] = ['foo'])
      // -- B (pre-bubbling, specifies #cache['contexts'] = ['b'])
      // --- C (pre-bubbling, specifies #cache['contexts'] = ['c'])
      // --- D (pre-bubbling, specifies #cache['contexts'] = ['d'])
      //
      // Additionally, suppose that:
      // - C only exists for a 'b' context value of 'b1'
      // - D only exists for a 'b' context value of 'b2'
      // This is an acceptable variation, since B specifies that its contents
      // vary on context 'b'.
      //
      // A naive implementation of cache redirection would result in the
      // following:
      // - When a request is processed where context 'b' = 'b1', what would be
      //   cached for a $pre_bubbling_cid of 'foo' is:
      //   [
      //     '#cache_redirect' => TRUE,
      //     '#cache' => [
      //       ...
      //       'contexts' => ['b', 'c'],
      //     ],
      //   ]
      // - When a request is processed where context 'b' = 'b2', we would
      //   retrieve the above from cache, but when following that redirection,
      //   get a cache miss, since we're processing a 'b' context value that
      //   has not yet been cached. Given the cache miss, we would continue
      //   with rendering the structure, perform the required context bubbling
      //   and then overwrite the above item with:
      //   [
      //     '#cache_redirect' => TRUE,
      //     '#cache' => [
      //       ...
      //       'contexts' => ['b', 'd'],
      //     ],
      //   ]
      // - Now, if a request comes in where context 'b' = 'b1' again, the above
      //   would redirect to a cache key that doesn't exist, since we have not
      //   yet cached an item that includes 'b'='b1' and something for 'd'. So
      //   we would process this request as a cache miss, at the end of which,
      //   we would overwrite the above item back to:
      //   [
      //     '#cache_redirect' => TRUE,
      //     '#cache' => [
      //       ...
      //       'contexts' => ['b', 'c'],
      //     ],
      //   ]
      // - The above would always result in accurate renderings, but would
      //   result in poor performance as we keep processing requests as cache
      //   misses even though the target of the redirection is cached, and
      //   it's only the redirection element itself that is creating the
      //   ping-pong problem.
      //
      // A way to resolve the ping-pong problem is to eventually reach a cache
      // state where the redirection element includes all of the contexts used
      // throughout all requests:
      // [
      //   '#cache_redirect' => TRUE,
      //   '#cache' => [
      //     ...
      //     'contexts' => ['b', 'c', 'd'],
      //   ],
      // ]
      //
      // We can't reach that state right away, since we don't know what the
      // result of future requests will be, but we can incrementally move
      // towards that state by progressively merging the 'contexts' value
      // across requests. That's the strategy employed below and tested in
      // \Drupal\Tests\Core\Render\RendererBubblingTest::testConditionalCacheContextBubblingSelfHealing().

      // The set of cache contexts for this element, including the bubbled ones,
      // for which we are handling a cache miss.
      $cache_contexts = $data['#cache']['contexts'];

      // Get the contexts by which this element should be varied according to
      // the current redirecting cache item, if any.
      $stored_cache_contexts = [];
      $stored_cache_tags = [];
      if ($stored_cache_redirect = $cache->get($pre_bubbling_cid)) {
        $stored_cache_contexts = $stored_cache_redirect->data['#cache']['contexts'];
        $stored_cache_tags = $stored_cache_redirect->data['#cache']['tags'];
      }

      // Calculate the union of the cache contexts for this request and the
      // stored cache contexts.
      $merged_cache_contexts = Cache::mergeContexts($stored_cache_contexts, $cache_contexts);

      // Stored cache contexts incomplete: this request causes cache contexts to
      // be added to the redirecting cache item.
      if (array_diff($merged_cache_contexts, $stored_cache_contexts)) {
        $redirect_data = [
          '#cache_redirect' => TRUE,
          '#cache' => [
            // The cache keys of the current element; this remains the same
            // across requests.
            'keys' => $elements['#cache']['keys'],
            // The union of the current element's and stored cache contexts.
            'contexts' => $merged_cache_contexts,
            // The union of the current element's and stored cache tags.
            'tags' => Cache::mergeTags($stored_cache_tags, $data['#cache']['tags']),
            // The same cache bin as the one for the actual render cache items.
            'bin' => $bin,
          ],
        ];
        $cache->set($pre_bubbling_cid, $redirect_data, $expire, Cache::mergeTags($redirect_data['#cache']['tags'], ['rendered']));
      }

      // Current cache contexts incomplete: this request only uses a subset of
      // the cache contexts stored in the redirecting cache item. Vary by these
      // additional (conditional) cache contexts as well, otherwise the
      // redirecting cache item would be pointing to a cache item that can never
      // exist.
      if (array_diff($merged_cache_contexts, $cache_contexts)) {
        // Recalculate the cache ID.
        $recalculated_cid_pseudo_element = [
          '#cache' => [
            'keys' => $elements['#cache']['keys'],
            'contexts' => $merged_cache_contexts,
          ]
        ];
        $cid = $this->createCacheID($recalculated_cid_pseudo_element);
        // Ensure the about-to-be-cached data uses the merged cache contexts.
        $data['#cache']['contexts'] = $merged_cache_contexts;
      }
    }
    $cache->set($cid, $data, $expire, Cache::mergeTags($data['#cache']['tags'], ['rendered']));
  }

  /**
   * Creates the cache ID for a renderable element.
   *
   * Creates the cache ID string based on #cache['keys'] + #cache['contexts'].
   *
   * @param array $elements
   *   A renderable array.
   *
   * @return string
   *   The cache ID string, or FALSE if the element may not be cached.
   */
  protected function createCacheID(array $elements) {
    // If the maximum age is zero, then caching is effectively prohibited.
    if (isset($elements['#cache']['max-age']) && $elements['#cache']['max-age'] === 0) {
      return FALSE;
    }

    if (isset($elements['#cache']['keys'])) {
      $cid_parts = $elements['#cache']['keys'];
      if (!empty($elements['#cache']['contexts'])) {
        $contexts = $this->cacheContextsManager->convertTokensToKeys($elements['#cache']['contexts']);
        $cid_parts = array_merge($cid_parts, $contexts);
      }
      return implode(':', $cid_parts);
    }
    return FALSE;
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
          $cacheable_items[$key] = ['#markup' => $cacheable_items[$key]['#markup']];
        }
      }
      $data += $cacheable_items;
    }

    return $data;
  }

}
