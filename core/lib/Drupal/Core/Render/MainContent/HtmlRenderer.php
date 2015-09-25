<?php

/**
 * @file
 * Contains \Drupal\Core\Render\MainContent\HtmlRenderer.
 */

namespace Drupal\Core\Render\MainContent;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\RenderEvents;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default main content renderer for HTML requests.
 *
 * For attachment handling of HTML responses:
 * @see template_preprocess_html()
 * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface
 * @see \Drupal\Core\Render\BareHtmlPageRenderer
 * @see \Drupal\Core\Render\HtmlResponse
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 */
class HtmlRenderer implements MainContentRendererInterface {

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The display variant manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $displayVariantManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The render cache service.
   *
   * @var \Drupal\Core\Render\RenderCacheInterface
   */
  protected $renderCache;

  /**
   * The renderer configuration array.
   *
   * @see sites/default/default.services.yml
   *
   * @var array
   */
  protected $rendererConfig;

  /**
   * Constructs a new HtmlRenderer.
   *
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $display_variant_manager
   *   The display variant manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache service.
   * @param array $renderer_config
   *   The renderer configuration array.
   */
  public function __construct(TitleResolverInterface $title_resolver, PluginManagerInterface $display_variant_manager, EventDispatcherInterface $event_dispatcher, ModuleHandlerInterface $module_handler, RendererInterface $renderer, RenderCacheInterface $render_cache, array $renderer_config) {
    $this->titleResolver = $title_resolver;
    $this->displayVariantManager = $display_variant_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->renderCache = $render_cache;
    $this->rendererConfig = $renderer_config;
  }

  /**
   * {@inheritdoc}
   *
   * The entire HTML: takes a #type 'page' and wraps it in a #type 'html'.
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    list($page, $title) = $this->prepare($main_content, $request, $route_match);

    if (!isset($page['#type']) || $page['#type'] !== 'page') {
      throw new \LogicException('Must be #type page');
    }

    $page['#title'] = $title;

    // Now render the rendered page.html.twig template inside the html.html.twig
    // template, and use the bubbled #attached metadata from $page to ensure we
    // load all attached assets.
    $html = [
      '#type' => 'html',
      'page' => $page,
    ];

    // The special page regions will appear directly in html.html.twig, not in
    // page.html.twig, hence add them here, just before rendering html.html.twig.
    $this->buildPageTopAndBottom($html);

    // Render, but don't replace placeholders yet, because that happens later in
    // the render pipeline. To not replace placeholders yet, we use
    // RendererInterface::render() instead of RendererInterface::renderRoot().
    // @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor.
    $render_context = new RenderContext();
    $this->renderer->executeInRenderContext($render_context, function() use (&$html) {
      // RendererInterface::render() renders the $html render array and updates
      // it in place. We don't care about the return value (which is just
      // $html['#markup']), but about the resulting render array.
      // @todo Simplify this when https://www.drupal.org/node/2495001 lands.
      $this->renderer->render($html);
    });
    // RendererInterface::render() always causes bubbleable metadata to be
    // stored in the render context, no need to check it conditionally.
    $bubbleable_metadata = $render_context->pop();
    $bubbleable_metadata->applyTo($html);
    $content = $this->renderCache->getCacheableRenderArray($html);

    // Also associate the required cache contexts.
    // (Because we use ::render() above and not ::renderRoot(), we manually must
    // ensure the HTML response varies by the required cache contexts.)
    $content['#cache']['contexts'] = Cache::mergeContexts($content['#cache']['contexts'], $this->rendererConfig['required_cache_contexts']);

    // Also associate the "rendered" cache tag. This allows us to invalidate the
    // entire render cache, regardless of the cache bin.
    $content['#cache']['tags'][] = 'rendered';

    $response = new HtmlResponse($content, 200, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ]);

    return $response;
  }

  /**
   * Prepares the HTML body: wraps the main content in #type 'page'.
   *
   * @param array $main_content
   *   The render array representing the main content.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object, for context.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match, for context.
   *
   * @return array
   *   An array with two values:
   *   0. A #type 'page' render array.
   *   1. The page title.
   *
   * @throws \LogicException
   *   If the selected display variant does not implement PageVariantInterface.
   */
  protected function prepare(array $main_content, Request $request, RouteMatchInterface $route_match) {
    // If the _controller result already is #type => page,
    // we have no work to do: The "main content" already is an entire "page"
    // (see html.html.twig).
    if (isset($main_content['#type']) && $main_content['#type'] === 'page') {
      $page = $main_content;
    }
    // Otherwise, render it as the main content of a #type => page, by selecting
    // page display variant to do that and building that page display variant.
    else {
      // Select the page display variant to be used to render this main content,
      // default to the built-in "simple page".
      $event = new PageDisplayVariantSelectionEvent('simple_page', $route_match);
      $this->eventDispatcher->dispatch(RenderEvents::SELECT_PAGE_DISPLAY_VARIANT, $event);
      $variant_id = $event->getPluginId();

      // We must render the main content now already, because it might provide a
      // title. We set its $is_root_call parameter to FALSE, to ensure
      // placeholders are not yet replaced. This is essentially "pre-rendering"
      // the main content, the "full rendering" will happen in
      // ::renderResponse().
      // @todo Remove this once https://www.drupal.org/node/2359901 lands.
      if (!empty($main_content)) {
        $this->renderer->executeInRenderContext(new RenderContext(), function() use (&$main_content) {
          if (isset($main_content['#cache']['keys'])) {
            // Retain #title, otherwise, dynamically generated titles would be
            // missing for controllers whose entire returned render array is
            // render cached.
            $main_content['#cache_properties'][] = '#title';
          }
          return $this->renderer->render($main_content, FALSE);
        });
        $main_content = $this->renderCache->getCacheableRenderArray($main_content) + [
          '#title' => isset($main_content['#title']) ? $main_content['#title'] : NULL
        ];
      }

      // Instantiate the page display, and give it the main content.
      $page_display = $this->displayVariantManager->createInstance($variant_id);
      if (!$page_display instanceof PageVariantInterface) {
        throw new \LogicException('Cannot render the main content for this page because the provided display variant does not implement PageVariantInterface.');
      }
      $page_display
        ->setMainContent($main_content)
        ->addCacheableDependency($event)
        ->setConfiguration($event->getPluginConfiguration());
      // Some display variants need to be passed an array of contexts with
      // values because they can't get all their contexts globally. For example,
      // in Page Manager, you can create a Page which has a specific static
      // context (e.g. a context that refers to the Node with nid 6), if any
      // such contexts were added to the $event, pass them to the $page_display.
      if ($page_display instanceof ContextAwareVariantInterface) {
        $page_display->setContexts($event->getContexts());
      }

      // Generate a #type => page render array using the page display variant,
      // the page display will build the content for the various page regions.
      $page = array(
        '#type' => 'page',
      );
      $page += $page_display->build();
    }

    // $page is now fully built. Find all non-empty page regions, and add a
    // theme wrapper function that allows them to be consistently themed.
    $regions = \Drupal::theme()->getActiveTheme()->getRegions();
    foreach ($regions as $region) {
      if (!empty($page[$region])) {
        $page[$region]['#theme_wrappers'][] = 'region';
        $page[$region]['#region'] = $region;
      }
    }

    // Allow hooks to add attachments to $page['#attached'].
    $this->invokePageAttachmentHooks($page);

    // Determine the title: use the title provided by the main content if any,
    // otherwise get it from the routing information.
    $title = isset($main_content['#title']) ? $main_content['#title'] : $this->titleResolver->getTitle($request, $route_match->getRouteObject());

    return [$page, $title];
  }

  /**
   * Invokes the page attachment hooks.
   *
   * @param array &$page
   *   A #type 'page' render array, for which the page attachment hooks will be
   *   invoked and to which the results will be added.
   *
   * @throws \LogicException
   *
   * @internal
   *
   * @see hook_page_attachments()
   * @see hook_page_attachments_alter()
   */
  public function invokePageAttachmentHooks(array &$page) {
    // Modules can add attachments.
    $attachments = [];
    foreach ($this->moduleHandler->getImplementations('page_attachments') as $module) {
      $function = $module . '_page_attachments';
      $function($attachments);
    }
    if (array_diff(array_keys($attachments), ['#attached', '#cache']) !== []) {
      throw new \LogicException('Only #attached and #cache may be set in hook_page_attachments().');
    }

    // Modules and themes can alter page attachments.
    $this->moduleHandler->alter('page_attachments', $attachments);
    \Drupal::theme()->alter('page_attachments', $attachments);
    if (array_diff(array_keys($attachments), ['#attached', '#cache']) !== []) {
      throw new \LogicException('Only #attached and #cache may be set in hook_page_attachments_alter().');
    }

    // Merge the attachments onto the $page render array.
    $page = $this->renderer->mergeBubbleableMetadata($page, $attachments);
  }

  /**
   * Invokes the page top and bottom hooks.
   *
   * @param array &$html
   *   A #type 'html' render array, for which the page top and bottom hooks will
   *   be invoked, and to which the 'page_top' and 'page_bottom' children (also
   *   render arrays) will be added (if non-empty).
   *
   * @throws \LogicException
   *
   * @internal
   *
   * @see hook_page_top()
   * @see hook_page_bottom()
   * @see html.html.twig
   */
  public function buildPageTopAndBottom(array &$html) {
    // Modules can add render arrays to the top and bottom of the page.
    $page_top = [];
    $page_bottom = [];
    foreach ($this->moduleHandler->getImplementations('page_top') as $module) {
      $function = $module . '_page_top';
      $function($page_top);
    }
    foreach ($this->moduleHandler->getImplementations('page_bottom') as $module) {
      $function = $module . '_page_bottom';
      $function($page_bottom);
    }
    if (!empty($page_top)) {
      $html['page_top'] = $page_top;
    }
    if (!empty($page_bottom)) {
      $html['page_bottom'] = $page_bottom;
    }
  }

}
