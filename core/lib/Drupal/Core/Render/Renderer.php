<?php

namespace Drupal\Core\Render;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Variable;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Form\FormHelper;
use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Security\DoTrustedCallbackTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Utility\CallableResolver;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Turns a render array into an HTML string.
 */
class Renderer implements RendererInterface {
  use DoTrustedCallbackTrait;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $theme;

  /**
   * The callable resolver.
   *
   * @var \Drupal\Core\Utility\CallableResolver
   */
  protected CallableResolver $callableResolver;

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
   * @param \Drupal\Core\Utility\CallableResolver|\Drupal\Core\Controller\ControllerResolverInterface $callable_resolver
   *   The callable resolver.
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
  public function __construct(ControllerResolverInterface|CallableResolver $callable_resolver, ThemeManagerInterface $theme, ElementInfoManagerInterface $element_info, PlaceholderGeneratorInterface $placeholder_generator, RenderCacheInterface $render_cache, RequestStack $request_stack, array $renderer_config) {
    if ($callable_resolver instanceof ControllerResolverInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with an argument of ControllerResolverInterface is deprecated in drupal:10.2.0 and is removed in drupal:11.0.0. Use \Drupal\Core\Utility\CallableResolver instead. See https://www.drupal.org/node/3369969', E_USER_DEPRECATED);
      $callable_resolver = \Drupal::service('callable_resolver');
    }
    $this->callableResolver = $callable_resolver;
    $this->theme = $theme;
    $this->elementInfo = $element_info;
    $this->placeholderGenerator = $placeholder_generator;
    $this->renderCache = $render_cache;
    if (!isset($renderer_config['debug'])) {
      $renderer_config['debug'] = FALSE;
    }
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
  public function renderInIsolation(&$elements) {
    return $this->executeInRenderContext(new RenderContext(), function () use (&$elements) {
      return $this->render($elements, TRUE);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function renderPlain(&$elements) {
    @trigger_error('Renderer::renderPlain() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Instead, you should use ::renderInIsolation(). See https://www.drupal.org/node/3407994', E_USER_DEPRECATED);
    return $this->renderInIsolation($elements);
  }

  /**
   * Renders a placeholder into markup.
   *
   * @param array $placeholder_element
   *   The placeholder element by reference.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The rendered HTML.
   */
  protected function doRenderPlaceholder(array &$placeholder_element): MarkupInterface|string {
    // Prevent the render array from being auto-placeholdered again.
    $placeholder_element['#create_placeholder'] = FALSE;

    // Render the placeholder into markup.
    $markup = $this->renderInIsolation($placeholder_element);
    return $markup;
  }

  /**
   * Replaces a placeholder with its markup.
   *
   * @param string $placeholder
   *   The placeholder HTML.
   * @param \Drupal\Component\Render\MarkupInterface|string $markup
   *   The markup to replace the placeholder with.
   * @param array $elements
   *   The render array that the placeholder is from.
   * @param array $placeholder_element
   *   The placeholder element render array.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The rendered HTML.
   */
  protected function doReplacePlaceholder(string $placeholder, string|MarkupInterface $markup, array $elements, array $placeholder_element): array {
    // Replace the placeholder with its rendered markup, and merge its
    // bubbleable metadata with the main elements'.
    $elements['#markup'] = Markup::create(str_replace($placeholder, $markup, $elements['#markup']));
    $elements = $this->mergeBubbleableMetadata($elements, $placeholder_element);

    // Remove the placeholder that we've just rendered.
    unset($elements['#attached']['placeholders'][$placeholder]);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function renderPlaceholder($placeholder, array $elements) {
    // Get the render array for the given placeholder
    $placeholder_element = $elements['#attached']['placeholders'][$placeholder];
    $markup = $this->doRenderPlaceholder($placeholder_element);
    return $this->doReplacePlaceholder($placeholder, $markup, $elements, $placeholder_element);
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

    if ($this->rendererConfig['debug'] === TRUE) {
      $render_start = microtime(TRUE);
    }

    if (!isset($elements['#access']) && isset($elements['#access_callback'])) {
      $elements['#access'] = $this->doCallback('#access_callback', $elements['#access_callback'], [$elements]);
    }

    // Early-return nothing if user does not have access.
    if (isset($elements['#access'])) {
      // If #access is an AccessResultInterface object, we must apply its
      // cacheability metadata to the render array.
      if ($elements['#access'] instanceof AccessResultInterface) {
        $this->addCacheableDependency($elements, $elements['#access']);
        if (!$elements['#access']->isAllowed()) {
          // Abort, but bubble new cache metadata from the access result.
          $context = $this->getCurrentRenderContext();
          if (!isset($context)) {
            throw new \LogicException("Render context is empty, because render() was called outside of a renderRoot() or renderInIsolation() call. Use renderInIsolation()/renderRoot() or #lazy_builder/#pre_render instead.");
          }
          $context->push(new BubbleableMetadata());
          $context->update($elements);
          $context->bubble();
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
      throw new \LogicException("Render context is empty, because render() was called outside of a renderRoot() or renderInIsolation() call. Use renderInIsolation()/renderRoot() or #lazy_builder/#pre_render instead.");
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
          $elements['#markup'] = Markup::create($elements['#markup']);
        }
        // Add debug output to the renderable array on cache hit.
        if ($this->rendererConfig['debug'] === TRUE) {
          $elements = $this->addDebugOutput($elements, TRUE);
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
      '#lazy_builder_preview' => TRUE,
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
      assert(is_array($elements['#lazy_builder']), 'The #lazy_builder property must have an array as a value.');
      assert(count($elements['#lazy_builder']) === 2, 'The #lazy_builder property must have an array as a value, containing two values: the callback, and the arguments for the callback.');
      assert(is_array($elements['#lazy_builder'][1]), 'The #lazy_builder argument for callback must have an array as a value.');
      assert(count($elements['#lazy_builder'][1]) === count(array_filter($elements['#lazy_builder'][1], function ($v) {
        return is_null($v) || is_scalar($v);
      })), "A #lazy_builder callback's context may only contain scalar values or NULL.");
      assert(!Element::children($elements), sprintf('When a #lazy_builder callback is specified, no children can exist; all children must be generated by the #lazy_builder callback. You specified the following children: %s.', implode(', ', Element::children($elements))));
      $supported_keys = [
        '#lazy_builder',
        '#cache',
        '#create_placeholder',
        '#lazy_builder_preview',
        '#preview',
        // The keys below are not actually supported, but these are added
        // automatically by the Renderer. Adding them as though they are
        // supported allows us to avoid throwing an exception 100% of the time.
        '#weight',
        '#printed',
      ];
      assert(empty(array_diff(array_keys($elements), $supported_keys)), sprintf('When a #lazy_builder callback is specified, no properties can exist; all properties must be generated by the #lazy_builder callback. You specified the following properties: %s.', implode(', ', array_diff(array_keys($elements), $supported_keys))));
    }
    // Determine whether to do auto-placeholdering.
    if ($this->placeholderGenerator->canCreatePlaceholder($elements) && $this->placeholderGenerator->shouldAutomaticallyPlaceholder($elements)) {
      $elements['#create_placeholder'] = TRUE;
    }
    // If instructed to create a placeholder, and a #lazy_builder callback is
    // present (without such a callback, it would be impossible to replace the
    // placeholder), replace the current element with a placeholder. On
    // uncacheable requests, always skip placeholdering - if a form is inside
    // a placeholder, which is likely, we want to render it as soon as possible,
    // so that form submission and redirection can take over before any more
    // content is rendered.
    if (isset($elements['#create_placeholder']) && $elements['#create_placeholder'] === TRUE && $this->requestStack->getCurrentRequest()->isMethodCacheable()) {
      if (!isset($elements['#lazy_builder'])) {
        throw new \LogicException('When #create_placeholder is set, a #lazy_builder callback must be present as well.');
      }
      $elements = $this->placeholderGenerator->createPlaceholder($elements);
    }
    // Build the element if it is still empty.
    if (isset($elements['#lazy_builder'])) {
      $new_elements = $this->doCallback('#lazy_builder', $elements['#lazy_builder'][0], $elements['#lazy_builder'][1]);
      // Throw an exception if #lazy_builder callback does not return an array;
      // provide helpful details for troubleshooting.
      assert(is_array($new_elements), "#lazy_builder callbacks must return a valid renderable array, got " . gettype($new_elements) . " from " . Variable::callableToString($elements['#lazy_builder'][0]));

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
        $elements = $this->doCallback('#pre_render', $callable, [$elements]);
      }
    }

    // All render elements support #markup and #plain_text.
    if (isset($elements['#markup']) || isset($elements['#plain_text'])) {
      $elements = $this->ensureMarkupIsSafe($elements);
    }

    // Defaults for bubbleable rendering metadata.
    $elements['#cache']['tags'] = $elements['#cache']['tags'] ?? [];
    $elements['#cache']['max-age'] = $elements['#cache']['max-age'] ?? Cache::PERMANENT;
    $elements['#attached'] = $elements['#attached'] ?? [];

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
      FormHelper::processStates($elements);
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
      $markup_keys = [
        '#description',
        '#field_prefix',
        '#field_suffix',
      ];
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
      $elements['#children'] = Markup::create($elements['#children']);
    }

    // If #theme is not implemented and the element has raw #markup as a
    // fallback, prepend the content in #markup to #children. In this case
    // #children will contain whatever is provided by #pre_render prepended to
    // what is rendered recursively above. If #theme is implemented then it is
    // the responsibility of that theme implementation to render #markup if
    // required. Eventually #theme_wrappers will expect both #markup and
    // #children to be a single string as #children.
    if (!$theme_is_implemented && isset($elements['#markup'])) {
      $elements['#children'] = Markup::create($elements['#markup'] . $elements['#children']);
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
        $elements['#children'] = $this->doCallback('#post_render', $callable, [$elements['#children'], $elements]);
      }
    }

    // We store the resulting output in $elements['#markup'], to be consistent
    // with how render cached output gets stored. This ensures that placeholder
    // replacement logic gets the same data to work with, no matter if #cache is
    // disabled, #cache is enabled, there is a cache hit or miss. If
    // #render_children is set the #prefix and #suffix will have already been
    // added.
    if (isset($elements['#render_children'])) {
      $elements['#markup'] = Markup::create($elements['#children']);
    }
    else {
      $prefix = isset($elements['#prefix']) ? $this->xssFilterAdminIfUnsafe($elements['#prefix']) : '';
      $suffix = isset($elements['#suffix']) ? $this->xssFilterAdminIfUnsafe($elements['#suffix']) : '';
      $elements['#markup'] = Markup::create($prefix . $elements['#children'] . $suffix);
    }

    // We've rendered this element (and its subtree!), now update the context.
    $context->update($elements);

    // Cache the processed element if both $pre_bubbling_elements and $elements
    // have the metadata necessary to generate a cache ID.
    if (isset($pre_bubbling_elements['#cache']['keys']) && isset($elements['#cache']['keys'])) {
      if ($pre_bubbling_elements['#cache']['keys'] !== $elements['#cache']['keys']) {
        throw new \LogicException('Cache keys may not be changed after initial setup. Use the contexts property instead to bubble additional metadata.');
      }
      $this->renderCache->set($elements, $pre_bubbling_elements);
      // Add debug output to the renderable array on cache miss.
      if ($this->rendererConfig['debug'] === TRUE) {
        $render_stop = microtime(TRUE);
        $elements = $this->addDebugOutput($elements, FALSE, $pre_bubbling_elements, $render_stop - $render_start);
      }
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
        throw new \LogicException('A stray RendererInterface::render() invocation with $is_root_call = TRUE is causing bubbling of attached assets to break.');
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
    assert($context->count() <= 1, 'Bubbling failed.');

    // Restore the original render context.
    $this->setCurrentRenderContext($previous_context);

    return $result;
  }

  /**
   * Returns the current render context.
   *
   * @return \Drupal\Core\Render\RenderContext|null
   *   The current render context.
   */
  protected function getCurrentRenderContext() {
    $request = $this->requestStack->getCurrentRequest();
    return static::$contextCollection[$request] ?? NULL;
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
  protected function setCurrentRenderContext(?RenderContext $context = NULL) {
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
   * @return bool
   *   Whether placeholders were replaced.
   *
   * @see \Drupal\Core\Render\Renderer::renderPlaceholder()
   */
  protected function replacePlaceholders(array &$elements) {
    if (!isset($elements['#attached']['placeholders']) || empty($elements['#attached']['placeholders'])) {
      return FALSE;
    }

    // The 'status messages' placeholder needs to be special cased, because it
    // depends on global state that can be modified when other placeholders are
    // being rendered: any code can add messages to render.
    // This violates the principle that each lazy builder must be able to render
    // itself in isolation, and therefore in any order. However, we cannot
    // change the way \Drupal\Core\Messenger\Messenger works in the Drupal 8
    // cycle. So we have to accommodate its special needs.
    // Allowing placeholders to be rendered in a particular order (in this case:
    // last) would violate this isolation principle. Thus a monopoly is granted
    // to this one special case, with this hard-coded solution.
    // @see \Drupal\Core\Render\Element\StatusMessages
    // @see https://www.drupal.org/node/2712935#comment-11368923

    // First render all placeholders except 'status messages' placeholders.
    $message_placeholders = [];
    $fibers = [];
    foreach ($elements['#attached']['placeholders'] as $placeholder => $placeholder_element) {
      if (isset($placeholder_element['#lazy_builder']) && $placeholder_element['#lazy_builder'][0] === 'Drupal\Core\Render\Element\StatusMessages::renderMessages') {
        $message_placeholders[] = $placeholder;
      }
      else {
        // Get the render array for the given placeholder
        $fibers[$placeholder] = new \Fiber(function () use ($placeholder_element) {
          return [$this->doRenderPlaceholder($placeholder_element), $placeholder_element];
        });
      }
    }
    $iterations = 0;
    while (count($fibers) > 0) {
      foreach ($fibers as $placeholder => $fiber) {
        if (!$fiber->isStarted()) {
          $fiber->start();
        }
        elseif ($fiber->isSuspended()) {
          $fiber->resume();
        }
        // If the Fiber hasn't terminated by this point, move onto the next
        // placeholder, we'll resume this fiber again when we get back here.
        if (!$fiber->isTerminated()) {
          // If we've gone through the placeholders once already, and they're
          // still not finished, then start to allow code higher up the stack to
          // get on with something else.
          if ($iterations) {
            $fiber = \Fiber::getCurrent();
            if ($fiber !== NULL) {
              $fiber->suspend();
            }
          }
          continue;
        }
        [$markup, $placeholder_element] = $fiber->getReturn();

        $elements = $this->doReplacePlaceholder($placeholder, $markup, $elements, $placeholder_element);
        unset($fibers[$placeholder]);
      }
      $iterations++;
    }

    // Then render 'status messages' placeholders.
    foreach ($message_placeholders as $message_placeholder) {
      $elements = $this->renderPlaceholder($message_placeholder, $elements);
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
   * @param string|\Drupal\Core\Render\Markup $string
   *   A string.
   *
   * @return \Drupal\Core\Render\Markup
   *   The escaped string wrapped in a Markup object. If the string is an
   *   instance of \Drupal\Component\Render\MarkupInterface, it won't be escaped
   *   again.
   */
  protected function xssFilterAdminIfUnsafe($string) {
    if (!($string instanceof MarkupInterface)) {
      $string = Xss::filterAdmin($string);
    }
    return Markup::create($string);
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
   * @return array
   *   The given array with the escaped markup wrapped in a Markup object.
   *   If $elements['#markup'] is an instance of
   *   \Drupal\Component\Render\MarkupInterface, it won't be escaped or filtered
   *   again.
   *
   * @see \Drupal\Component\Utility\Html::escape()
   * @see \Drupal\Component\Utility\Xss::filter()
   * @see \Drupal\Component\Utility\Xss::filterAdmin()
   */
  protected function ensureMarkupIsSafe(array $elements) {
    if (isset($elements['#plain_text'])) {
      $elements['#markup'] = Markup::create(Html::escape($elements['#plain_text']));
    }
    elseif (!($elements['#markup'] instanceof MarkupInterface)) {
      // The default behavior is to XSS filter using the admin tag list.
      $tags = $elements['#allowed_tags'] ?? Xss::getAdminTagList();
      $elements['#markup'] = Markup::create(Xss::filter($elements['#markup'], $tags));
    }

    return $elements;
  }

  /**
   * Performs a callback.
   *
   * @param string $callback_type
   *   The type of the callback. For example, '#post_render'.
   * @param string|callable $callback
   *   The callback to perform.
   * @param array $args
   *   The arguments to pass to the callback.
   *
   * @return mixed
   *   The callback's return value.
   *
   * @see \Drupal\Core\Security\TrustedCallbackInterface
   */
  protected function doCallback($callback_type, $callback, array $args) {
    $callable = $this->callableResolver->getCallableFromDefinition($callback);
    $message = sprintf('Render %s callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was %s. See https://www.drupal.org/node/2966725', $callback_type, '%s');
    // Add \Drupal\Core\Render\Element\RenderCallbackInterface as an extra
    // trusted interface so that:
    // - All public methods on Render elements are considered trusted.
    // - Helper classes that contain only callback methods can implement this
    //   instead of TrustedCallbackInterface.
    return $this->doTrustedCallback($callable, $args, $message, TrustedCallbackInterface::THROW_EXCEPTION, RenderCallbackInterface::class);
  }

  /**
   * Add cache debug information to the render array.
   *
   * @param array $elements
   *   The renderable array that must be wrapped with the cache debug output.
   * @param bool $is_cache_hit
   *   A flag indicating that the cache is hit or miss.
   * @param array $pre_bubbling_elements
   *   The renderable array for pre-bubbling elements.
   * @param float $render_time
   *   The rendering time.
   *
   * @return array
   *   The renderable array.
   */
  protected function addDebugOutput(array $elements, bool $is_cache_hit, array $pre_bubbling_elements = [], float $render_time = 0) {
    if (empty($elements['#markup'])) {
      return $elements;
    }

    $debug_items = [
      'CACHE' => &$elements,
      'PRE-BUBBLING CACHE' => &$pre_bubbling_elements,
    ];
    $prefix = "<!-- START RENDERER -->";
    $prefix .= "\n<!-- CACHE-HIT: " . ($is_cache_hit ? 'Yes' : 'No') . " -->";
    foreach ($debug_items as $name_prefix => $debug_item) {
      if (!empty($debug_item['#cache']['tags'])) {
        $prefix .= "\n<!-- " . $name_prefix . " TAGS:";
        foreach ($debug_item['#cache']['tags'] as $tag) {
          $prefix .= "\n   * " . $tag;
        }
        $prefix .= "\n-->";
      }
      if (!empty($debug_item['#cache']['contexts'])) {
        $prefix .= "\n<!-- " . $name_prefix . " CONTEXTS:";
        foreach ($debug_item['#cache']['contexts'] as $context) {
          $prefix .= "\n   * " . $context;
        }
        $prefix .= "\n-->";
      }
      if (!empty($debug_item['#cache']['keys'])) {
        $prefix .= "\n<!-- " . $name_prefix . " KEYS:";
        foreach ($debug_item['#cache']['keys'] as $key) {
          $prefix .= "\n   * " . $key;
        }
        $prefix .= "\n-->";
      }
      if (!empty($debug_item['#cache']['max-age'])) {
        $prefix .= "\n<!-- " . $name_prefix . " MAX-AGE: " . $debug_item['#cache']['max-age'] . " -->";
      }
    }

    if (!empty($render_time)) {
      $prefix .= "\n<!-- RENDERING TIME: " . number_format($render_time, 9) . " -->";
    }
    $suffix = "<!-- END RENDERER -->";

    $elements['#markup'] = Markup::create("$prefix\n" . $elements['#markup'] . "\n$suffix");

    return $elements;
  }

}
