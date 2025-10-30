<?php

declare(strict_types=1);

namespace Drupal\Core\Render\MainContent;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Main content renderer for HTMX requests.
 *
 * This renderer is invoked when:
 * - The HTMX request adds the `_wrapper_format` query parameter with value
 *   `drupal_htmx`.
 * - The route has the `_htmx_route` option set to TRUE.
 *
 * Attachments in HTML responses are handled by
 * \Drupal\Core\Render\AttachmentsResponseProcessorInterface and
 * \Drupal\Core\Render\HtmlResponseAttachmentsProcessor.
 *
 * @see \Drupal\Core\EventSubscriber\MainContentViewSubscriber
 * @see \Drupal\Core\Theme\ThemePreprocess::preprocessHtml()
 * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface
 * @see \Drupal\Core\Render\BareHtmlPageRenderer
 * @see \Drupal\Core\Render\HtmlResponse
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 * @see \Drupal\Core\EventSubscriber\MainContentViewSubscriber
 * @see \Drupal\Core\EventSubscriber\HtmxContentViewSubscriber
 */
class HtmxRenderer implements MainContentRendererInterface {

  /**
   * Constructs a new HtmxRenderer.
   */
  public function __construct(
    protected TitleResolverInterface $titleResolver,
    protected RendererInterface $renderer,
    protected RenderCacheInterface $renderCache,
    protected array $rendererConfig,
  ) {
  }

  /**
   * {@inheritdoc}
   *
   * We only wrap the necessary content into a full HTML document to be
   * processed by HTMX on the frontend.
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $token = Crypt::randomBytesBase64(55);
    $html = [
      '#type' => 'inline_template',
      // Add a noindex meta tag to make sure this response will not be indexed.
      '#template' => <<<HTMX_RESPONSE
<!doctype html>
<html>
<head>
<meta name="robots" content="noindex">
<title>{{ title }}</title>
<css-placeholder token="{{ placeholder_token }}">
<js-placeholder token="{{ placeholder_token }}">
<js-bottom-placeholder token="{{ placeholder_token }}">
</head>
<body>{{ content }}</body>
</html>
HTMX_RESPONSE,
      '#context' => [
        'title' => $main_content['#title'] ?? $this->titleResolver->getTitle($request, $route_match->getRouteObject()),
        'content' => [
          'messages' => ['#type' => 'status_messages'],
          'main_content' => $main_content,
        ],
        'placeholder_token' => $token,
      ],
    ];
    // Create placeholder strings for these keys.
    // @see \Drupal\Core\Render\HtmlResponseSubscriber
    $types = [
      'styles' => 'css',
      'scripts' => 'js',
      'scripts_bottom' => 'js-bottom',
    ];
    foreach ($types as $type => $placeholder_name) {
      $placeholder = '<' . $placeholder_name . '-placeholder token="' . $token . '">';
      $html['#attached']['html_response_attachment_placeholders'][$type] = $placeholder;
    }

    // Render, but don't replace placeholders yet, because that happens later in
    // the render pipeline. To not replace placeholders yet, we use
    // RendererInterface::render() instead of RendererInterface::renderRoot().
    // @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor.
    $render_context = new RenderContext();
    $this->renderer->executeInRenderContext($render_context, function () use (&$html) {
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
      // Make sure bots do not show this response in search results.
      'X-Robots-Tag' => 'noindex',
    ]);

    return $response;
  }

}
