<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Renderer.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheContexts;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Turns a render array into a HTML string.
 */
class Renderer implements RendererInterface {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $theme;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The element info.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

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
   * The cache contexts service.
   *
   * @var \Drupal\Core\Cache\CacheContexts
   */
  protected $cacheContexts;

  /**
   * The renderer configuration array.
   *
   * @var array
   */
  protected $rendererConfig;

  /**
   * The stack containing bubbleable rendering metadata.
   *
   * @var \SplStack|null
   */
  protected static $stack;

  /**
   * Constructs a new Renderer.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme
   *   The theme manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cache_factory
   *   The cache factory.
   * @param \Drupal\Core\Cache\CacheContexts $cache_contexts
   *   The cache contexts service.
   * @param array $renderer_config
   *   The renderer configuration array.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, ThemeManagerInterface $theme, ElementInfoManagerInterface $element_info, RequestStack $request_stack, CacheFactoryInterface $cache_factory, CacheContexts $cache_contexts, array $renderer_config) {
    $this->controllerResolver = $controller_resolver;
    $this->theme = $theme;
    $this->elementInfo = $element_info;
    $this->requestStack = $request_stack;
    $this->cacheFactory = $cache_factory;
    $this->cacheContexts = $cache_contexts;
    $this->rendererConfig = $renderer_config;
  }

  /**
   * {@inheritdoc}
   */
  public function renderRoot(&$elements) {
    return $this->render($elements, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function renderPlain(&$elements) {
    $current_stack = static::$stack;
    $this->resetStack();
    $output = $this->renderRoot($elements);
    static::$stack = $current_stack;
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function render(&$elements, $is_root_call = FALSE) {
    // Since #pre_render, #post_render, #post_render_cache callbacks and theme
    // functions/templates may be used for generating a render array's content,
    // and we might be rendering the main content for the page, it is possible
    // that any of them throw an exception that will cause a different page to
    // be rendered (e.g. throwing
    // \Symfony\Component\HttpKernel\Exception\NotFoundHttpException will cause
    // the 404 page to be rendered). That page might also use Renderer::render()
    // but if exceptions aren't caught here, the stack will be left in an
    // inconsistent state.
    // Hence, catch all exceptions and reset the stack and re-throw them.
    try {
      return $this->doRender($elements, $is_root_call);
    }
    catch (\Exception $e) {
      // Reset stack and re-throw exception.
      $this->resetStack();
      throw $e;
    }
  }

  /**
   * See the docs for ::render().
   */
  protected function doRender(&$elements, $is_root_call = FALSE) {
    if (!isset($elements['#access']) && isset($elements['#access_callback'])) {
      if (is_string($elements['#access_callback']) && strpos($elements['#access_callback'], '::') === FALSE) {
        $elements['#access_callback'] = $this->controllerResolver->getControllerFromDefinition($elements['#access_callback']);
      }
      $elements['#access'] = call_user_func($elements['#access_callback'], $elements);
    }

    // Early-return nothing if user does not have access.
    if (empty($elements) || (isset($elements['#access']) && !$elements['#access'])) {
      return '';
    }

    // Do not print elements twice.
    if (!empty($elements['#printed'])) {
      return '';
    }

    if (!isset(static::$stack)) {
      static::$stack = new \SplStack();
    }
    static::$stack->push(new BubbleableMetadata());

    // Set the bubbleable rendering metadata that has configurable defaults, if:
    // - this is the root call, to ensure that the final render array definitely
    //   has these configurable defaults, even when no subtree is render cached.
    // - this is a render cacheable subtree, to ensure that the cached data has
    //   the configurable defaults (which may affect the ID and invalidation).
    if ($is_root_call || isset($elements['#cache']['keys'])) {
      $required_cache_contexts = $this->rendererConfig['required_cache_contexts'];
      if (isset($elements['#cache']['contexts'])) {
        $elements['#cache']['contexts'] = Cache::mergeContexts($elements['#cache']['contexts'], $required_cache_contexts);
      }
      else {
        $elements['#cache']['contexts'] = $required_cache_contexts;
      }
    }

    // Try to fetch the prerendered element from cache, run any
    // #post_render_cache callbacks and return the final markup.
    $pre_bubbling_cid = NULL;
    if (isset($elements['#cache']['keys'])) {
      $cached_element = $this->cacheGet($elements);
      if ($cached_element !== FALSE) {
        $elements = $cached_element;
        // Only when we're not in a root (non-recursive) drupal_render() call,
        // #post_render_cache callbacks must be executed, to prevent breaking
        // the render cache in case of nested elements with #cache set.
        if ($is_root_call) {
          $this->processPostRenderCache($elements);
        }
        $elements['#markup'] = SafeMarkup::set($elements['#markup']);
        // The render cache item contains all the bubbleable rendering metadata
        // for the subtree.
        $this->updateStack($elements);
        // Render cache hit, so rendering is finished, all necessary info
        // collected!
        $this->bubbleStack();
        return $elements['#markup'];
      }
      else {
        // Two-tier caching: set pre-bubbling cache ID, if this element is
        // cacheable..
        // @see ::cacheGet()
        // @see ::cacheSet()
        if ($this->requestStack->getCurrentRequest()->isMethodSafe() && $cid = $this->createCacheID($elements)) {
          $pre_bubbling_cid = $cid;
        }
      }
    }

    // If the default values for this element have not been loaded yet, populate
    // them.
    if (isset($elements['#type']) && empty($elements['#defaults_loaded'])) {
      $elements += $this->elementInfo->getInfo($elements['#type']);
    }

    // Make any final changes to the element before it is rendered. This means
    // that the $element or the children can be altered or corrected before the
    // element is rendered into the final text.
    if (isset($elements['#pre_render'])) {
      foreach ($elements['#pre_render'] as $callable) {
        if (is_string($callable) && strpos($callable, '::') === FALSE) {
          $callable = $this->controllerResolver->getControllerFromDefinition($callable);
        }
        $elements = call_user_func($callable, $elements);
      }
    }

    // Defaults for bubbleable rendering metadata.
    $elements['#cache']['tags'] = isset($elements['#cache']['tags']) ? $elements['#cache']['tags'] : array();
    $elements['#cache']['max-age'] = isset($elements['#cache']['max-age']) ? $elements['#cache']['max-age'] : Cache::PERMANENT;
    $elements['#attached'] = isset($elements['#attached']) ? $elements['#attached'] : array();
    $elements['#post_render_cache'] = isset($elements['#post_render_cache']) ? $elements['#post_render_cache'] : array();

    // Allow #pre_render to abort rendering.
    if (!empty($elements['#printed'])) {
      // The #printed element contains all the bubbleable rendering metadata for
      // the subtree.
      $this->updateStack($elements);
      // #printed, so rendering is finished, all necessary info collected!
      $this->bubbleStack();
      return '';
    }

    // Add any JavaScript state information associated with the element.
    if (!empty($elements['#states'])) {
      drupal_process_states($elements);
    }

    // Get the children of the element, sorted by weight.
    $children = Element::children($elements, TRUE);

    // Initialize this element's #children, unless a #pre_render callback
    // already preset #children.
    if (!isset($elements['#children'])) {
      $elements['#children'] = '';
    }

    // @todo Simplify after https://drupal.org/node/2273925
    if (isset($elements['#markup'])) {
      $elements['#markup'] = SafeMarkup::set($elements['#markup']);
    }

    // Assume that if #theme is set it represents an implemented hook.
    $theme_is_implemented = isset($elements['#theme']);
    // Check the elements for insecure HTML and pass through sanitization.
    if (isset($elements)) {
      $markup_keys = array(
        '#description',
        '#field_prefix',
        '#field_suffix',
      );
      foreach ($markup_keys as $key) {
        if (!empty($elements[$key]) && is_scalar($elements[$key])) {
          $elements[$key] = SafeMarkup::checkAdminXss($elements[$key]);
        }
      }
    }

    // Call the element's #theme function if it is set. Then any children of the
    // element have to be rendered there. If the internal #render_children
    // property is set, do not call the #theme function to prevent infinite
    // recursion.
    if ($theme_is_implemented && !isset($elements['#render_children'])) {
      $elements['#children'] = $this->theme->render($elements['#theme'], $elements);

      // If ThemeManagerInterface::render() returns FALSE this means that the
      // hook in #theme was not found in the registry and so we need to update
      // our flag accordingly. This is common for theme suggestions.
      $theme_is_implemented = ($elements['#children'] !== FALSE);
    }

    // If #theme is not implemented or #render_children is set and the element
    // has an empty #children attribute, render the children now. This is the
    // same process as Renderer::render() but is inlined for speed.
    if ((!$theme_is_implemented || isset($elements['#render_children'])) && empty($elements['#children'])) {
      foreach ($children as $key) {
        $elements['#children'] .= $this->doRender($elements[$key]);
      }
      $elements['#children'] = SafeMarkup::set($elements['#children']);
    }

    // If #theme is not implemented and the element has raw #markup as a
    // fallback, prepend the content in #markup to #children. In this case
    // #children will contain whatever is provided by #pre_render prepended to
    // what is rendered recursively above. If #theme is implemented then it is
    // the responsibility of that theme implementation to render #markup if
    // required. Eventually #theme_wrappers will expect both #markup and
    // #children to be a single string as #children.
    if (!$theme_is_implemented && isset($elements['#markup'])) {
      $elements['#children'] = SafeMarkup::set($elements['#markup'] . $elements['#children']);
    }

    // Let the theme functions in #theme_wrappers add markup around the rendered
    // children.
    // #states and #attached have to be processed before #theme_wrappers,
    // because the #type 'page' render array from drupal_prepare_page() would
    // render the $page and wrap it into the html.html.twig template without the
    // attached assets otherwise.
    // If the internal #render_children property is set, do not call the
    // #theme_wrappers function(s) to prevent infinite recursion.
    if (isset($elements['#theme_wrappers']) && !isset($elements['#render_children'])) {
      foreach ($elements['#theme_wrappers'] as $key => $value) {
        // If the value of a #theme_wrappers item is an array then the theme
        // hook is found in the key of the item and the value contains attribute
        // overrides. Attribute overrides replace key/value pairs in $elements
        // for only this ThemeManagerInterface::render() call. This allows
        // #theme hooks and #theme_wrappers hooks to share variable names
        // without conflict or ambiguity.
        $wrapper_elements = $elements;
        if (is_string($key)) {
          $wrapper_hook = $key;
          foreach ($value as $attribute => $override) {
            $wrapper_elements[$attribute] = $override;
          }
        }
        else {
          $wrapper_hook = $value;
        }

        $elements['#children'] = $this->theme->render($wrapper_hook, $wrapper_elements);
      }
    }

    // Filter the outputted content and make any last changes before the content
    // is sent to the browser. The changes are made on $content which allows the
    // outputted text to be filtered.
    if (isset($elements['#post_render'])) {
      foreach ($elements['#post_render'] as $callable) {
        if (is_string($callable) && strpos($callable, '::') === FALSE) {
          $callable = $this->controllerResolver->getControllerFromDefinition($callable);
        }
        $elements['#children'] = call_user_func($callable, $elements['#children'], $elements);
      }
    }

    // We store the resulting output in $elements['#markup'], to be consistent
    // with how render cached output gets stored. This ensures that
    // #post_render_cache callbacks get the same data to work with, no matter if
    // #cache is disabled, #cache is enabled, there is a cache hit or miss.
    $prefix = isset($elements['#prefix']) ? SafeMarkup::checkAdminXss($elements['#prefix']) : '';
    $suffix = isset($elements['#suffix']) ? SafeMarkup::checkAdminXss($elements['#suffix']) : '';

    $elements['#markup'] = $prefix . $elements['#children'] . $suffix;

    // We've rendered this element (and its subtree!), now update the stack.
    $this->updateStack($elements);

    // Cache the processed element if #cache is set, and the metadata necessary
    // to generate a cache ID is present.
    if (isset($elements['#cache']) && isset($elements['#cache']['keys'])) {
      $this->cacheSet($elements, $pre_bubbling_cid);
    }

    // Only when we're in a root (non-recursive) drupal_render() call,
    // #post_render_cache callbacks must be executed, to prevent breaking the
    // render cache in case of nested elements with #cache set.
    //
    // By running them here, we ensure that:
    // - they run when #cache is disabled,
    // - they run when #cache is enabled and there is a cache miss.
    // Only the case of a cache hit when #cache is enabled, is not handled here,
    // that is handled earlier in Renderer::render().
    if ($is_root_call) {
      // We've already called ::updateStack() earlier, which updated both the
      // element and current stack frame. However,
      // Renderer::processPostRenderCache() can both change the element
      // further and create and render new child elements, so provide a fresh
      // stack frame to collect those additions, merge them back to the element,
      // and then update the current frame to match the modified element state.
      do {
        static::$stack->push(new BubbleableMetadata());
        $this->processPostRenderCache($elements);
        $post_render_additions = static::$stack->pop();
        $elements['#post_render_cache'] = NULL;
        BubbleableMetadata::createFromRenderArray($elements)
          ->merge($post_render_additions)
          ->applyTo($elements);
      } while (!empty($elements['#post_render_cache']));
      if (static::$stack->count() !== 1) {
        throw new \LogicException('A stray drupal_render() invocation with $is_root_call = TRUE is causing bubbling of attached assets to break.');
      }
    }

    // Rendering is finished, all necessary info collected!
    $this->bubbleStack();

    $elements['#printed'] = TRUE;
    $elements['#markup'] = SafeMarkup::set($elements['#markup']);
    return $elements['#markup'];
  }

  /**
   * Resets the renderer service's internal stack (used for bubbling metadata).
   *
   * Only necessary in very rare/advanced situations, such as when rendering an
   * error page if an exception occurred *during* rendering.
   */
  protected function resetStack() {
    static::$stack = NULL;
  }

  /**
   * Updates the stack.
   *
   * @param array &$element
   *   The element of the render array that has just been rendered. The stack
   *   frame for this element will be updated with the bubbleable rendering
   *   metadata of this element.
   */
  protected function updateStack(&$element) {
    // The latest frame represents the bubbleable metadata for the subtree.
    $frame = static::$stack->pop();
    // Update the frame, but also update the current element, to ensure it
    // contains up-to-date information in case it gets render cached.
    $updated_frame = BubbleableMetadata::createFromRenderArray($element)->merge($frame);
    $updated_frame->applyTo($element);
    static::$stack->push($updated_frame);
  }

  /**
   * Bubbles the stack.
   *
   * Whenever another level in the render array has been rendered, the stack
   * must be bubbled, to merge its rendering metadata with that of the parent
   * element.
   */
  protected function bubbleStack() {
    // If there's only one frame on the stack, then this is the root call, and
    // we can't bubble up further. Reset the stack for the next root call.
    if (static::$stack->count() === 1) {
      $this->resetStack();
      return;
    }

    // Merge the current and the parent stack frame.
    $current = static::$stack->pop();
    $parent = static::$stack->pop();
    static::$stack->push($current->merge($parent));
  }

  /**
   * Processes #post_render_cache callbacks.
   *
   * #post_render_cache callbacks may modify:
   * - #markup: to replace placeholders
   * - #attached: to add libraries or JavaScript settings
   * - #post_render_cache: to execute additional #post_render_cache callbacks
   *
   * Note that in either of these cases, #post_render_cache callbacks are
   * implicitly idempotent: a placeholder that has been replaced can't be
   * replaced again, and duplicate attachments are ignored.
   *
   * @param array &$elements
   *   The structured array describing the data being rendered.
   */
  protected function processPostRenderCache(array &$elements) {
    if (isset($elements['#post_render_cache'])) {

      // Call all #post_render_cache callbacks, passing the provided context.
      foreach (array_keys($elements['#post_render_cache']) as $callback) {
        if (strpos($callback, '::') === FALSE) {
          $callable = $this->controllerResolver->getControllerFromDefinition($callback);
        }
        else {
          $callable = $callback;
        }
        foreach ($elements['#post_render_cache'][$callback] as $context) {
          $elements = call_user_func_array($callable, array($elements, $context));
        }
      }
    }
  }

  /**
   * Gets the cached, prerendered element of a renderable element from the cache.
   *
   * @param array $elements
   *   A renderable array.
   *
   * @return array
   *   A renderable array, with the original element and all its children pre-
   *   rendered, or FALSE if no cached copy of the element is available.
   *
   * @see ::render()
   * @see ::saveToCache()
   */
  protected function cacheGet(array $elements) {
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
      // @see ::doRender()
      // @see ::cacheSet()
      if (isset($cached_element['#cache_redirect'])) {
        return $this->cacheGet($cached_element);
      }
      // Return the cached element.
      return $cached_element;
    }
    return FALSE;
  }

  /**
   * Caches the rendered output of a renderable element.
   *
   * This is called by ::render() if the #cache property is set on an element.
   *
   * @param array $elements
   *   A renderable array.
   * @param string|null $pre_bubbling_cid
   *   The pre-bubbling cache ID.
   *
   * @return bool|null
   *  Returns FALSE if no cache item could be created, NULL otherwise.
   *
   * @see ::getFromCache()
   */
  protected function cacheSet(array &$elements, $pre_bubbling_cid) {
    // Form submissions rely on the form being built during the POST request,
    // and render caching of forms prevents this from happening.
    // @todo remove the isMethodSafe() check when
    //       https://www.drupal.org/node/2367555 lands.
    if (!$this->requestStack->getCurrentRequest()->isMethodSafe() || !$cid = $this->createCacheID($elements)) {
      return FALSE;
    }

    $data = $this->getCacheableRenderArray($elements);

    $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'render';
    $expire = ($elements['#cache']['max-age'] === Cache::PERMANENT) ? Cache::PERMANENT : REQUEST_TIME + $elements['#cache']['max-age'];
    $cache = $this->cacheFactory->get($bin);

    // Two-tier caching: detect different CID post-bubbling, create redirect,
    // update redirect if different set of cache contexts.
    // @see ::doRender()
    // @see ::cacheGet()
    if (isset($pre_bubbling_cid) && $pre_bubbling_cid !== $cid) {
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
        $contexts = $this->cacheContexts->convertTokensToKeys($elements['#cache']['contexts']);
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
    return [
      '#markup' => $elements['#markup'],
      '#attached' => $elements['#attached'],
      '#post_render_cache' => $elements['#post_render_cache'],
      '#cache' => [
        'contexts' => $elements['#cache']['contexts'],
        'tags' => $elements['#cache']['tags'],
        'max-age' => $elements['#cache']['max-age'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mergeBubbleableMetadata(array $a, array $b) {
    $meta_a = BubbleableMetadata::createFromRenderArray($a);
    $meta_b = BubbleableMetadata::createFromRenderArray($b);
    $meta_a->merge($meta_b)->applyTo($a);
    return $a;
  }

  /**
   * {@inheritdoc}
   */
  public static function mergeAttachments(array $a, array $b) {
    // If both #attached arrays contain drupalSettings, then merge them
    // correctly; adding the same settings multiple times needs to behave
    // idempotently.
    if (!empty($a['drupalSettings']) && !empty($b['drupalSettings'])) {
      $a['drupalSettings'] = NestedArray::mergeDeepArray([$a['drupalSettings'], $b['drupalSettings']], TRUE);
      unset($b['drupalSettings']);
    }
    return NestedArray::mergeDeep($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function generateCachePlaceholder($callback, array &$context) {
    if (is_string($callback) && strpos($callback, '::') === FALSE) {
      $callable = $this->controllerResolver->getControllerFromDefinition($callback);
    }
    else {
      $callable = $callback;
    }

    if (!is_callable($callable)) {
      throw new \InvalidArgumentException('$callable must be a callable function or of the form service_id:method.');
    }

    // Generate a unique token if one is not already provided.
    $context += [
      'token' => Crypt::randomBytesBase64(55),
    ];

    return '<drupal-render-cache-placeholder callback="' . $callback . '" token="' . $context['token'] . '"></drupal-render-cache-placeholder>';
  }

}
