<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Renderer.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ThemeManagerInterface;

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
   * The render cache service.
   *
   * @var \Drupal\Core\Render\RenderCacheInterface
   */
  protected $renderCache;

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
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache service.
   * @param array $renderer_config
   *   The renderer configuration array.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, ThemeManagerInterface $theme, ElementInfoManagerInterface $element_info, RenderCacheInterface $render_cache, array $renderer_config) {
    $this->controllerResolver = $controller_resolver;
    $this->theme = $theme;
    $this->elementInfo = $element_info;
    $this->renderCache = $render_cache;
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
   * Renders final HTML for a placeholder.
   *
   * Renders the placeholder in isolation.
   *
   * @param string $placeholder
   *   An attached placeholder to render. (This must be a key of one of the
   *   values of $elements['#attached']['placeholders'].)
   * @param array $elements
   *   The structured array describing the data to be rendered.
   *
   * @return array
   *   The updated $elements.
   *
   * @see ::replacePlaceholders()
   *
   * @todo Make public as part of https://www.drupal.org/node/2469431
   */
  protected function renderPlaceholder($placeholder, array $elements) {
    // Get the render array for the given placeholder
    $placeholder_elements = $elements['#attached']['placeholders'][$placeholder];

    // Render the placeholder into markup.
    $markup = $this->renderPlain($placeholder_elements);

    // Replace the placeholder with its rendered markup, and merge its
    // bubbleable metadata with the main elements'.
    $elements['#markup'] = str_replace($placeholder, $markup, $elements['#markup']);
    $elements = $this->mergeBubbleableMetadata($elements, $placeholder_elements);

    // Remove the placeholder that we've just rendered.
    unset($elements['#attached']['placeholders'][$placeholder]);

    return $elements;
  }


  /**
   * {@inheritdoc}
   */
  public function render(&$elements, $is_root_call = FALSE) {
    // Since #pre_render, #post_render, #lazy_builder callbacks and theme
    // functions or templates may be used for generating a render array's
    // content, and we might be rendering the main content for the page, it is
    // possible that any of them throw an exception that will cause a different
    // page to be rendered (e.g. throwing
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

    // Try to fetch the prerendered element from cache, replace any placeholders
    // and return the final markup.
    if (isset($elements['#cache']['keys'])) {
      $cached_element = $this->renderCache->get($elements);
      if ($cached_element !== FALSE) {
        $elements = $cached_element;
        // Only when we're in a root (non-recursive) Renderer::render() call,
        // placeholders must be processed, to prevent breaking the render cache
        // in case of nested elements with #cache set.
        if ($is_root_call) {
          $this->replacePlaceholders($elements);
        }
        // Mark the element markup as safe. If we have cached children, we need
        // to mark them as safe too. The parent markup contains the child
        // markup, so if the parent markup is safe, then the markup of the
        // individual children must be safe as well.
        $elements['#markup'] = SafeMarkup::set($elements['#markup']);
        if (!empty($elements['#cache_properties'])) {
          foreach (Element::children($cached_element) as $key) {
            SafeMarkup::set($cached_element[$key]['#markup']);
          }
        }
        // The render cache item contains all the bubbleable rendering metadata
        // for the subtree.
        $this->updateStack($elements);
        // Render cache hit, so rendering is finished, all necessary info
        // collected!
        $this->bubbleStack();
        return $elements['#markup'];
      }
    }
    // Two-tier caching: track pre-bubbling elements' #cache for later
    // comparison.
    // @see \Drupal\Core\Render\RenderCacheInterface::get()
    // @see \Drupal\Core\Render\RenderCacheInterface::set()
    $pre_bubbling_elements = [];
    $pre_bubbling_elements['#cache'] = isset($elements['#cache']) ? $elements['#cache'] : [];

    // If the default values for this element have not been loaded yet, populate
    // them.
    if (isset($elements['#type']) && empty($elements['#defaults_loaded'])) {
      $elements += $this->elementInfo->getInfo($elements['#type']);
    }

    // First validate the usage of #lazy_builder; both of the next if-statements
    // use it if available.
    if (isset($elements['#lazy_builder'])) {
      // @todo Convert to assertions once https://www.drupal.org/node/2408013
      //   lands.
      if (!is_array($elements['#lazy_builder'])) {
        throw new \DomainException('The #lazy_builder property must have an array as a value.');
      }
      if (count($elements['#lazy_builder']) !== 2) {
        throw new \DomainException('The #lazy_builder property must have an array as a value, containing two values: the callback, and the arguments for the callback.');
      }
      if (count($elements['#lazy_builder'][1]) !== count(array_filter($elements['#lazy_builder'][1], function($v) { return is_null($v) || is_scalar($v); }))) {
        throw new \DomainException("A #lazy_builder callback's context may only contain scalar values or NULL.");
      }
      $children = Element::children($elements);
      if ($children) {
        throw new \DomainException(sprintf('When a #lazy_builder callback is specified, no children can exist; all children must be generated by the #lazy_builder callback. You specified the following children: %s.', implode(', ', $children)));
      }
      $supported_keys = [
        '#lazy_builder',
        '#cache',
        '#create_placeholder',
        // These keys are not actually supported, but they are added automatically
        // by the Renderer, so we don't crash on them; them being missing when
        // their #lazy_builder callback is invoked won't surprise the developer.
        '#weight',
        '#printed'
      ];
      $unsupported_keys = array_diff(array_keys($elements), $supported_keys);
      if (count($unsupported_keys)) {
        throw new \DomainException(sprintf('When a #lazy_builder callback is specified, no properties can exist; all properties must be generated by the #lazy_builder callback. You specified the following properties: %s.', implode(', ', $unsupported_keys)));
      }
    }
    // If instructed to create a placeholder, and a #lazy_builder callback is
    // present (without such a callback, it would be impossible to replace the
    // placeholder), replace the current element with a placeholder.
    if (isset($elements['#create_placeholder']) && $elements['#create_placeholder'] === TRUE) {
      if (!isset($elements['#lazy_builder'])) {
        throw new \LogicException('When #create_placeholder is set, a #lazy_builder callback must be present as well.');
      }
      $elements = $this->createPlaceholder($elements);
    }
    // Build the element if it is still empty.
    if (isset($elements['#lazy_builder'])) {
      $callable = $elements['#lazy_builder'][0];
      $args = $elements['#lazy_builder'][1];
      if (is_string($callable) && strpos($callable, '::') === FALSE) {
        $callable = $this->controllerResolver->getControllerFromDefinition($callable);
      }
      $new_elements = call_user_func_array($callable, $args);
      // Retain the original cacheability metadata, plus cache keys.
      CacheableMetadata::createFromRenderArray($elements)
        ->merge(CacheableMetadata::createFromRenderArray($new_elements))
        ->applyTo($new_elements);
      if (isset($elements['#cache']['keys'])) {
        $new_elements['#cache']['keys'] = $elements['#cache']['keys'];
      }
      $elements = $new_elements;
      $elements['#lazy_builder_built'] = TRUE;
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

    if (isset($elements['#markup'])) {
      // @todo Decide how to support non-HTML in the render API in
      //   https://www.drupal.org/node/2501313.
      $elements['#markup'] = SafeMarkup::checkAdminXss($elements['#markup']);
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
    // with how render cached output gets stored. This ensures that placeholder
    // replacement logic gets the same data to work with, no matter if #cache is
    // disabled, #cache is enabled, there is a cache hit or miss.
    $prefix = isset($elements['#prefix']) ? SafeMarkup::checkAdminXss($elements['#prefix']) : '';
    $suffix = isset($elements['#suffix']) ? SafeMarkup::checkAdminXss($elements['#suffix']) : '';

    $elements['#markup'] = $prefix . $elements['#children'] . $suffix;

    // We've rendered this element (and its subtree!), now update the stack.
    $this->updateStack($elements);

    // Cache the processed element if both $pre_bubbling_elements and $elements
    // have the metadata necessary to generate a cache ID.
    if (isset($pre_bubbling_elements['#cache']['keys']) && isset($elements['#cache']['keys'])) {
      if ($pre_bubbling_elements['#cache']['keys'] !== $elements['#cache']['keys']) {
        throw new \LogicException('Cache keys may not be changed after initial setup. Use the contexts property instead to bubble additional metadata.');
      }
      $this->renderCache->set($elements, $pre_bubbling_elements);
    }

    // Only when we're in a root (non-recursive) Renderer::render() call,
    // placeholders must be processed, to prevent breaking the render cache in
    // case of nested elements with #cache set.
    //
    // By running them here, we ensure that:
    // - they run when #cache is disabled,
    // - they run when #cache is enabled and there is a cache miss.
    // Only the case of a cache hit when #cache is enabled, is not handled here,
    // that is handled earlier in Renderer::render().
    if ($is_root_call) {
      $this->replacePlaceholders($elements);
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
   * Replaces placeholders.
   *
   * Placeholders may have:
   * - #lazy_builder callback, to build a render array to be rendered into
   *   markup that can replace the placeholder
   * - #cache: to cache the result of the placeholder
   *
   * Also merges the bubbleable metadata resulting from the rendering of the
   * contents of the placeholders. Hence $elements will be contain the entirety
   * of bubbleable metadata.
   *
   * @param array &$elements
   *   The structured array describing the data being rendered. Including the
   *   bubbleable metadata associated with the markup that replaced the
   *   placeholders.
   *
   * @returns bool
   *   Whether placeholders were replaced.
   */
  protected function replacePlaceholders(array &$elements) {
    if (!isset($elements['#attached']['placeholders']) || empty($elements['#attached']['placeholders'])) {
      return FALSE;
    }

    foreach (array_keys($elements['#attached']['placeholders']) as $placeholder) {
      $elements = $this->renderPlaceholder($placeholder, $elements);
    }

    return TRUE;
  }

  /**
   * Turns this element into a placeholder.
   *
   * Placeholdering allows us to avoid "poor cacheability contamination": this
   * maps the current render array to one that only has #markup and #attached,
   * and #attached contains a placeholder with this element's prior cacheability
   * metadata. In other words: this placeholder is perfectly cacheable, the
   * placeholder replacement logic effectively cordons off poor cacheability.
   *
   * @param array $element
   *   The render array to create a placeholder for.
   *
   * @return array
   *   Render array with placeholder markup and the attached placeholder
   *   replacement metadata.
   */
  protected function createPlaceholder(array $element) {
    $placeholder_render_array = array_intersect_key($element, [
      // Placeholders are replaced with markup by executing the associated
      // #lazy_builder callback, which generates a render array, and which the
      // Renderer will render and replace the placeholder with.
      '#lazy_builder' => TRUE,
      // The cacheability metadata for the placeholder. The rendered result of
      // the placeholder may itself be cached, if [#cache][keys] are specified.
      '#cache' => TRUE,
    ]);

    // Generate placeholder markup. Note that the only requirement is that this
    // is unique markup that isn't easily guessable. The #lazy_builder callback
    // and its arguments are put in the placeholder markup solely to simplify
    // debugging.
    $attributes = new Attribute();
    $attributes['callback'] = $placeholder_render_array['#lazy_builder'][0];
    $attributes['arguments'] = UrlHelper::buildQuery($placeholder_render_array['#lazy_builder'][1]);
    $attributes['token'] = hash('sha1', serialize($placeholder_render_array));
    $placeholder_markup = SafeMarkup::format('<drupal-render-placeholder@attributes></drupal-render-placeholder>', ['@attributes' => $attributes]);

    // Build the placeholder element to return.
    $placeholder_element = [];
    $placeholder_element['#markup'] = $placeholder_markup;
    $placeholder_element['#attached']['placeholders'][$placeholder_markup] = $placeholder_render_array;
    return $placeholder_element;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeBubbleableMetadata(array $a, array $b) {
    $meta_a = BubbleableMetadata::createFromRenderArray($a);
    $meta_b = BubbleableMetadata::createFromRenderArray($b);
    $meta_a->merge($meta_b)->applyTo($a);
    return $a;
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheableDependency(array &$elements, $dependency) {
    $meta_a = CacheableMetadata::createFromRenderArray($elements);
    $meta_b = CacheableMetadata::createFromObject($dependency);
    $meta_a->merge($meta_b)->applyTo($elements);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeAttachments(array $a, array $b) {
    // If both #attached arrays contain drupalSettings, then merge them
    // correctly; adding the same settings multiple times needs to behave
    // idempotently.
    if (!empty($a['drupalSettings']) && !empty($b['drupalSettings'])) {
      $drupalSettings = NestedArray::mergeDeepArray(array($a['drupalSettings'], $b['drupalSettings']), TRUE);
      // No need for re-merging them.
      unset($a['drupalSettings']);
      unset($b['drupalSettings']);
    }
    // Optimize merging of placeholders: no need for deep merging.
    if (!empty($a['placeholders']) && !empty($b['placeholders'])) {
      $placeholders = $a['placeholders'] + $b['placeholders'];
      // No need for re-merging them.
      unset($a['placeholders']);
      unset($b['placeholders']);
    }
    // Apply the normal merge.
    $a = array_merge_recursive($a, $b);
    if (isset($drupalSettings)) {
      // Save the custom merge for the drupalSettings.
      $a['drupalSettings'] = $drupalSettings;
    }
    if (isset($placeholders)) {
      // Save the custom merge for the placeholders.
      $a['placeholders'] = $placeholders;
    }
    return $a;
  }

}
