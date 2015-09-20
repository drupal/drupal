<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Renderer.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
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
   * The placeholder generator.
   *
   * @var \Drupal\Core\Render\PlaceholderGeneratorInterface
   */
  protected $placeholderGenerator;

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
   * Whether we're currently in a ::renderRoot() call.
   *
   * @var bool
   */
  protected $isRenderingRoot = FALSE;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The render context collection.
   *
   * An individual global render context is tied to the current request. We then
   * need to maintain a different context for each request to correctly handle
   * rendering in subrequests.
   *
   * This must be static as long as some controllers rebuild the container
   * during a request. This causes multiple renderer instances to co-exist
   * simultaneously, render state getting lost, and therefore causing pages to
   * fail to render correctly. As soon as it is guaranteed that during a request
   * the same container is used, it no longer needs to be static.
   *
   * @var \Drupal\Core\Render\RenderContext[]
   */
  protected static $contextCollection;

  /**
   * Constructs a new Renderer.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme
   *   The theme manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info.
   * @param \Drupal\Core\Render\PlaceholderGeneratorInterface $placeholder_generator
   *   The placeholder generator.
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param array $renderer_config
   *   The renderer configuration array.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, ThemeManagerInterface $theme, ElementInfoManagerInterface $element_info, PlaceholderGeneratorInterface $placeholder_generator, RenderCacheInterface $render_cache, RequestStack $request_stack, array $renderer_config) {
    $this->controllerResolver = $controller_resolver;
    $this->theme = $theme;
    $this->elementInfo = $element_info;
    $this->placeholderGenerator = $placeholder_generator;
    $this->renderCache = $render_cache;
    $this->rendererConfig = $renderer_config;
    $this->requestStack = $request_stack;

    // Initialize the context collection if needed.
    if (!isset(static::$contextCollection)) {
      static::$contextCollection = new \SplObjectStorage();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renderRoot(&$elements) {
    // Disallow calling ::renderRoot() from within another ::renderRoot() call.
    if ($this->isRenderingRoot) {
      $this->isRenderingRoot = FALSE;
      throw new \LogicException('A stray renderRoot() invocation is causing bubbling of attached assets to break.');
    }

    // Render in its own render context.
    $this->isRenderingRoot = TRUE;
    $output = $this->executeInRenderContext(new RenderContext(), function () use (&$elements) {
      return $this->render($elements, TRUE);
    });
    $this->isRenderingRoot = FALSE;

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function renderPlain(&$elements) {
    return $this->executeInRenderContext(new RenderContext(), function () use (&$elements) {
      return $this->render($elements, TRUE);
    });
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

    // Prevent the render array from being auto-placeholdered again.
    $placeholder_elements['#create_placeholder'] = FALSE;

    // Render the placeholder into markup.
    $markup = $this->renderPlain($placeholder_elements);

    // Replace the placeholder with its rendered markup, and merge its
    // bubbleable metadata with the main elements'.
    $elements['#markup'] = SafeString::create(str_replace($placeholder, $markup, $elements['#markup']));
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
    // the 404 page to be rendered). That page might also use
    // Renderer::renderRoot() but if exceptions aren't caught here, it will be
    // impossible to call Renderer::renderRoot() again.
    // Hence, catch all exceptions, reset the isRenderingRoot property and
    // re-throw exceptions.
    try {
      return $this->doRender($elements, $is_root_call);
    }
    catch (\Exception $e) {
      // Mark the ::rootRender() call finished due to this exception & re-throw.
      $this->isRenderingRoot = FALSE;
      throw $e;
    }
  }

  /**
   * See the docs for ::render().
   */
  protected function doRender(&$elements, $is_root_call = FALSE) {
    if (empty($elements)) {
      return '';
    }

    if (!isset($elements['#access']) && isset($elements['#access_callback'])) {
      if (is_string($elements['#access_callback']) && strpos($elements['#access_callback'], '::') === FALSE) {
        $elements['#access_callback'] = $this->controllerResolver->getControllerFromDefinition($elements['#access_callback']);
      }
      $elements['#access'] = call_user_func($elements['#access_callback'], $elements);
    }

    // Early-return nothing if user does not have access.
    if (isset($elements['#access'])) {
      // If #access is an AccessResultInterface object, we must apply it's
      // cacheability metadata to the render array.
      if ($elements['#access'] instanceof AccessResultInterface) {
        $this->addCacheableDependency($elements, $elements['#access']);
        if (!$elements['#access']->isAllowed()) {
          return '';
        }
      }
      elseif ($elements['#access'] === FALSE) {
        return '';
      }
    }

    // Do not print elements twice.
    if (!empty($elements['#printed'])) {
      return '';
    }

    $context = $this->getCurrentRenderContext();
    if (!isset($context)) {
      throw new \LogicException("Render context is empty, because render() was called outside of a renderRoot() or renderPlain() call. Use renderPlain()/renderRoot() or #lazy_builder/#pre_render instead.");
    }
    $context->push(new BubbleableMetadata());

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
        // Mark the element markup as safe if is it a string.
        if (is_string($elements['#markup'])) {
          $elements['#markup'] = SafeString::create($elements['#markup']);
        }
        // The render cache item contains all the bubbleable rendering metadata
        // for the subtree.
        $context->update($elements);
        // Render cache hit, so rendering is finished, all necessary info
        // collected!
        $context->bubble();
        return $elements['#markup'];
      }
    }
    // Two-tier caching: track pre-bubbling elements' #cache, #lazy_builder and
    // #create_placeholder for later comparison.
    // @see \Drupal\Core\Render\RenderCacheInterface::get()
    // @see \Drupal\Core\Render\RenderCacheInterface::set()
    $pre_bubbling_elements = array_intersect_key($elements, [
      '#cache' => TRUE,
      '#lazy_builder' => TRUE,
      '#create_placeholder' => TRUE,
    ]);

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
    // Determine whether to do auto-placeholdering.
    if ($this->placeholderGenerator->canCreatePlaceholder($elements) && $this->placeholderGenerator->shouldAutomaticallyPlaceholder($elements)) {
      $elements['#create_placeholder'] = TRUE;
    }
    // If instructed to create a placeholder, and a #lazy_builder callback is
    // present (without such a callback, it would be impossible to replace the
    // placeholder), replace the current element with a placeholder.
    if (isset($elements['#create_placeholder']) && $elements['#create_placeholder'] === TRUE) {
      if (!isset($elements['#lazy_builder'])) {
        throw new \LogicException('When #create_placeholder is set, a #lazy_builder callback must be present as well.');
      }
      $elements = $this->placeholderGenerator->createPlaceholder($elements);
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

    // All render elements support #markup and #plain_text.
    if (!empty($elements['#markup']) || !empty($elements['#plain_text'])) {
      $elements = $this->ensureMarkupIsSafe($elements);
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
      $context->update($elements);
      // #printed, so rendering is finished, all necessary info collected!
      $context->bubble();
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
          $elements[$key] = $this->xssFilterAdminIfUnsafe($elements[$key]);
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
      $elements['#children'] = SafeString::create($elements['#children']);
    }

    // If #theme is not implemented and the element has raw #markup as a
    // fallback, prepend the content in #markup to #children. In this case
    // #children will contain whatever is provided by #pre_render prepended to
    // what is rendered recursively above. If #theme is implemented then it is
    // the responsibility of that theme implementation to render #markup if
    // required. Eventually #theme_wrappers will expect both #markup and
    // #children to be a single string as #children.
    if (!$theme_is_implemented && isset($elements['#markup'])) {
      $elements['#children'] = SafeString::create($elements['#markup'] . $elements['#children']);
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
    $prefix = isset($elements['#prefix']) ? $this->xssFilterAdminIfUnsafe($elements['#prefix']) : '';
    $suffix = isset($elements['#suffix']) ? $this->xssFilterAdminIfUnsafe($elements['#suffix']) : '';

    $elements['#markup'] = SafeString::create($prefix . $elements['#children'] . $suffix);

    // We've rendered this element (and its subtree!), now update the context.
    $context->update($elements);

    // Cache the processed element if both $pre_bubbling_elements and $elements
    // have the metadata necessary to generate a cache ID.
    if (isset($pre_bubbling_elements['#cache']['keys']) && isset($elements['#cache']['keys'])) {
      if ($pre_bubbling_elements['#cache']['keys'] !== $elements['#cache']['keys']) {
        throw new \LogicException('Cache keys may not be changed after initial setup. Use the contexts property instead to bubble additional metadata.');
      }
      $this->renderCache->set($elements, $pre_bubbling_elements);
      // Update the render context; the render cache implementation may update
      // the element, and it may have different bubbleable metadata now.
      // @see \Drupal\Core\Render\PlaceholderingRenderCache::set()
      $context->pop();
      $context->push(new BubbleableMetadata());
      $context->update($elements);
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
      // @todo remove as part of https://www.drupal.org/node/2511330.
      if ($context->count() !== 1) {
        throw new \LogicException('A stray drupal_render() invocation with $is_root_call = TRUE is causing bubbling of attached assets to break.');
      }
    }

    // Rendering is finished, all necessary info collected!
    $context->bubble();

    $elements['#printed'] = TRUE;
    return $elements['#markup'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasRenderContext() {
    return (bool) $this->getCurrentRenderContext();
  }

  /**
   * {@inheritdoc}
   */
  public function executeInRenderContext(RenderContext $context, callable $callable) {
    // Store the current render context.
    $previous_context = $this->getCurrentRenderContext();

    // Set the provided context and call the callable, it will use that context.
    $this->setCurrentRenderContext($context);
    $result = $callable();
    // @todo Convert to an assertion in https://www.drupal.org/node/2408013
    if ($context->count() > 1) {
      throw new \LogicException('Bubbling failed.');
    }

    // Restore the original render context.
    $this->setCurrentRenderContext($previous_context);

    return $result;
  }

  /**
   * Returns the current render context.
   *
   * @return \Drupal\Core\Render\RenderContext
   *   The current render context.
   */
  protected function getCurrentRenderContext() {
    $request = $this->requestStack->getCurrentRequest();
    return isset(static::$contextCollection[$request]) ? static::$contextCollection[$request] : NULL;
  }

  /**
   * Sets the current render context.
   *
   * @param \Drupal\Core\Render\RenderContext|null $context
   *   The render context. This can be NULL for instance when restoring the
   *   original render context, which is in fact NULL.
   *
   * @return $this
   */
  protected function setCurrentRenderContext(RenderContext $context = NULL) {
    $request = $this->requestStack->getCurrentRequest();
    static::$contextCollection[$request] = $context;
    return $this;
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
   * Applies a very permissive XSS/HTML filter for admin-only use.
   *
   * Note: This method only filters if $string is not marked safe already. This
   * ensures that HTML intended for display is not filtered.
   *
   * @param string|\Drupal\Core\Render\SafeString $string
   *   A string.
   *
   * @return \Drupal\Core\Render\SafeString
   *   The escaped string wrapped in a SafeString object. If
   *   SafeMarkup::isSafe($string) returns TRUE, it won't be escaped again.
   */
  protected function xssFilterAdminIfUnsafe($string) {
    if (!SafeMarkup::isSafe($string)) {
      $string = Xss::filterAdmin($string);
    }
    return SafeString::create($string);
  }

  /**
   * Escapes #plain_text or filters #markup as required.
   *
   * Drupal uses Twig's auto-escape feature to improve security. This feature
   * automatically escapes any HTML that is not known to be safe. Due to this
   * the render system needs to ensure that all markup it generates is marked
   * safe so that Twig does not do any additional escaping.
   *
   * By default all #markup is filtered to protect against XSS using the admin
   * tag list. Render arrays can alter the list of tags allowed by the filter
   * using the #allowed_tags property. This value should be an array of tags
   * that Xss::filter() would accept. Render arrays can escape text instead
   * of XSS filtering by setting the #plain_text property instead of #markup. If
   * #plain_text is used #allowed_tags is ignored.
   *
   * @param array $elements
   *   A render array with #markup set.
   *
   * @return \Drupal\Component\Utility\SafeStringInterface|string
   *   The escaped markup wrapped in a SafeString object. If
   *   SafeMarkup::isSafe($elements['#markup']) returns TRUE, it won't be
   *   escaped or filtered again.
   *
   * @see \Drupal\Component\Utility\Html::escape()
   * @see \Drupal\Component\Utility\Xss::filter()
   * @see \Drupal\Component\Utility\Xss::adminFilter()
   */
  protected function ensureMarkupIsSafe(array $elements) {
    if (empty($elements['#markup']) && empty($elements['#plain_text'])) {
      return $elements;
    }

    if (!empty($elements['#plain_text'])) {
      $elements['#markup'] = SafeString::create(Html::escape($elements['#plain_text']));
    }
    elseif (!SafeMarkup::isSafe($elements['#markup'])) {
      // The default behaviour is to XSS filter using the admin tag list.
      $tags = isset($elements['#allowed_tags']) ? $elements['#allowed_tags'] : Xss::getAdminTagList();
      $elements['#markup'] = SafeString::create(Xss::filter($elements['#markup'], $tags));
    }

    return $elements;
  }

}
