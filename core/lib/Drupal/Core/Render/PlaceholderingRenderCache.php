<?php

namespace Drupal\Core\Render;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds automatic placeholdering to the RenderCache.
 *
 * This automatic placeholdering is performed to ensure the containing elements
 * and overarching response are as cacheable as possible. Elements whose subtree
 * bubble either max-age=0 or high-cardinality cache contexts (such as 'user'
 * and 'session') are considered poorly cacheable.
 *
 * @see sites/default/default.services.yml
 *
 * Automatic placeholdering is performed only on elements whose subtree was
 * generated using a #lazy_builder callback and whose bubbled cacheability meets
 * the auto-placeholdering conditions as configured in the renderer.config
 * container parameter.
 *
 * This RenderCache implementation automatically replaces an element with a
 * placeholder:
 * - on render cache hit, i.e. ::get()
 * - on render cache miss, i.e. ::set() (in subsequent requests, it will be a
 *   cache hit)
 *
 * In either case, the render cache is guaranteed to contain the to-be-rendered
 * placeholder, so replacing (rendering) the placeholder will be very fast.
 *
 * Finally, in case the render cache item disappears between the time it is
 * decided to automatically placeholder the element and the time where the
 * placeholder is replaced (rendered), that is guaranteed to not be problematic.
 * Because this only automatically placeholders elements that have a
 * #lazy_builder callback set, which means that in the worst case, it will need
 * to be re-rendered.
 */
class PlaceholderingRenderCache extends RenderCache {

  /**
   * The placeholder generator.
   *
   * @var \Drupal\Core\Render\PlaceholderGeneratorInterface
   */
  protected $placeholderGenerator;

  /**
   * Stores rendered results for automatically placeholdered elements.
   *
   * This allows us to avoid talking to the cache twice per auto-placeholdered
   * element, or in case of an uncacheable element, to render it twice.
   *
   * Scenario A. The double cache read would happen because:
   * 1. when rendering, cache read, but auto-placeholdered
   * 2. when rendering placeholders, again cache read
   *
   * Scenario B. The cache write plus read would happen because:
   * 1. when rendering, cache write, but auto-placeholdered
   * 2. when rendering placeholders, cache read
   *
   * Scenario C. The double rendering for an uncacheable element would happen because:
   * 1. when rendering, not cacheable, but auto-placeholdered
   * 2. when rendering placeholders, rendered again
   *
   * In all three scenarios, this static cache avoids the second step, thus
   * avoiding expensive work.
   *
   * @var array
   */
  protected $placeholderResultsCache = [];

  /**
   * Constructs a new PlaceholderingRenderCache object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Cache\VariationCacheFactoryInterface $cache_factory
   *   The variation cache factory.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager.
   * @param \Drupal\Core\Render\PlaceholderGeneratorInterface $placeholder_generator
   *   The placeholder generator.
   */
  public function __construct(RequestStack $request_stack, $cache_factory, CacheContextsManager $cache_contexts_manager, PlaceholderGeneratorInterface $placeholder_generator) {
    if ($cache_factory instanceof CacheFactoryInterface) {
      @trigger_error('Injecting ' . __CLASS__ . ' with the "cache_factory" service is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use "variation_cache_factory" instead. See https://www.drupal.org/node/3365546', E_USER_DEPRECATED);
      $cache_factory = \Drupal::service('variation_cache_factory');
    }
    parent::__construct($request_stack, $cache_factory, $cache_contexts_manager);
    $this->placeholderGenerator = $placeholder_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function get(array $elements) {

    // When rendering placeholders, special case auto-placeholdered elements:
    // avoid retrieving them from cache again, or rendering them again.
    if (isset($elements['#create_placeholder']) && $elements['#create_placeholder'] === FALSE) {
      $cached_placeholder_result = $this->getFromPlaceholderResultsCache($elements);
      if ($cached_placeholder_result !== FALSE) {
        return $cached_placeholder_result;
      }
    }

    $cached_element = parent::get($elements);

    if ($cached_element === FALSE) {
      return FALSE;
    }
    else {
      if ($this->placeholderGenerator->canCreatePlaceholder($elements) && $this->placeholderGenerator->shouldAutomaticallyPlaceholder($cached_element)) {
        return $this->createPlaceholderAndRemember($cached_element, $elements);
      }

      return $cached_element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set(array &$elements, array $pre_bubbling_elements) {
    $result = parent::set($elements, $pre_bubbling_elements);

    // Writes to the render cache are disabled on uncacheable HTTP requests, to
    // prevent very low hit rate items from being written. If we're not writing
    // to the cache, there's also no benefit to placeholdering either.
    if (!$this->requestStack->getCurrentRequest()->isMethodCacheable()) {
      return FALSE;
    }

    if ($this->placeholderGenerator->canCreatePlaceholder($pre_bubbling_elements) && $this->placeholderGenerator->shouldAutomaticallyPlaceholder($elements)) {
      // Overwrite $elements with a placeholder. The Renderer (which called this
      // method) will update the context with the bubbleable metadata of the
      // overwritten $elements.
      $elements = $this->createPlaceholderAndRemember($this->getCacheableRenderArray($elements), $pre_bubbling_elements);
    }

    return $result;
  }

  /**
   * Create a placeholder for a renderable array and remember in a static cache.
   *
   * @param array $rendered_elements
   *   A fully rendered renderable array.
   * @param array $pre_bubbling_elements
   *   A renderable array corresponding to the state (in particular, the
   *   cacheability metadata) of $rendered_elements prior to the beginning of
   *   its rendering process, and therefore before any bubbling of child
   *   information has taken place. Only the #cache property is used by this
   *   function, so the caller may omit all other properties and children from
   *   this array.
   *
   * @return array
   *   Renderable array with placeholder markup and the attached placeholder
   *   replacement metadata.
   */
  protected function createPlaceholderAndRemember(array $rendered_elements, array $pre_bubbling_elements) {
    $placeholder_element = $this->placeholderGenerator->createPlaceholder($pre_bubbling_elements);
    // Remember the result for this placeholder to avoid double work.
    $placeholder = (string) $placeholder_element['#markup'];
    $this->placeholderResultsCache[$placeholder] = $rendered_elements;
    return $placeholder_element;
  }

  /**
   * Retrieves an auto-placeholdered renderable array from the static cache.
   *
   * @param array $elements
   *   A renderable array.
   *
   * @return array|false
   *   A renderable array, with the original element and all its children pre-
   *   rendered, or FALSE if no cached copy of the element is available.
   */
  protected function getFromPlaceholderResultsCache(array $elements) {
    $placeholder_element = $this->placeholderGenerator->createPlaceholder($elements);
    $placeholder = (string) $placeholder_element['#markup'];
    if (isset($this->placeholderResultsCache[$placeholder])) {
      return $this->placeholderResultsCache[$placeholder];
    }
    return FALSE;
  }

}
