<?php
/**
 * @file
 * Contains \Drupal\Core\Render\HtmlResponseAttachmentsProcessor.
 */

namespace Drupal\Core\Render;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes attachments of HTML responses.
 *
 * @see template_preprocess_html()
 * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface
 * @see \Drupal\Core\Render\BareHtmlPageRenderer
 * @see \Drupal\Core\Render\HtmlResponse
 * @see \Drupal\Core\Render\MainContent\HtmlRenderer
 */
class HtmlResponseAttachmentsProcessor implements AttachmentsResponseProcessorInterface {

  /**
   * The asset resolver service.
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The CSS asset collection renderer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $cssCollectionRenderer;

  /**
   * The JS asset collection renderer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $jsCollectionRenderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a HtmlResponseAttachmentsProcessor object.
   *
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   *   An asset resolver.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_collection_renderer
   *   The CSS asset collection renderer.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_collection_renderer
   *   The JS asset collection renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(AssetResolverInterface $asset_resolver, ConfigFactoryInterface $config_factory, AssetCollectionRendererInterface $css_collection_renderer, AssetCollectionRendererInterface $js_collection_renderer, RequestStack $request_stack, RendererInterface $renderer) {
    $this->assetResolver = $asset_resolver;
    $this->config = $config_factory->get('system.performance');
    $this->cssCollectionRenderer = $css_collection_renderer;
    $this->jsCollectionRenderer = $js_collection_renderer;
    $this->requestStack = $request_stack;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    // @todo Convert to assertion once https://www.drupal.org/node/2408013 lands
    if (!$response instanceof HtmlResponse) {
      throw new \InvalidArgumentException('\Drupal\Core\Render\HtmlResponse instance expected.');
    }

    // First, render the actual placeholders; this may cause additional
    // attachments to be added to the response, which the attachment
    // placeholders rendered by renderHtmlResponseAttachmentPlaceholders() will
    // need to include.
    $response = $this->renderPlaceholders($response);

    $attached = $response->getAttachments();

    // Get the placeholders from attached and then remove them.
    $attachment_placeholders = $attached['html_response_attachment_placeholders'];
    unset($attached['html_response_attachment_placeholders']);

    $variables = $this->processAssetLibraries($attached, $attachment_placeholders);

    // Handle all non-asset attachments. This populates drupal_get_html_head().
    $all_attached = ['#attached' => $attached];
    drupal_process_attached($all_attached);

    // Get HTML head elements - if present.
    if (isset($attachment_placeholders['head'])) {
      $variables['head'] = drupal_get_html_head(FALSE);
    }

    // Now replace the attachment placeholders.
    $this->renderHtmlResponseAttachmentPlaceholders($response, $attachment_placeholders, $variables);

    // Finally set the headers on the response if any bubbled.
    if (!empty($attached['http_header'])) {
      $this->setHeaders($response, $attached['http_header']);
    }

    return $response;
  }

  /**
   * Renders placeholders (#attached['placeholders']).
   *
   * First, the HTML response object is converted to an equivalent render array,
   * with #markup being set to the response's content and #attached being set to
   * the response's attachments. Among these attachments, there may be
   * placeholders that need to be rendered (replaced).
   *
   * Next, RendererInterface::renderRoot() is called, which renders the
   * placeholders into their final markup.
   *
   * The markup that results from RendererInterface::renderRoot() is now the
   * original HTML response's content, but with the placeholders rendered. We
   * overwrite the existing content in the original HTML response object with
   * this markup. The markup that was rendered for the placeholders may also
   * have attachments (e.g. for CSS/JS assets) itself, and cacheability metadata
   * that indicates what that markup depends on. That metadata is also added to
   * the HTML response object.
   *
   * @param \Drupal\Core\Render\HtmlResponse $response
   *   The HTML response whose placeholders are being replaced.
   *
   * @return \Drupal\Core\Render\HtmlResponse
   *   The updated HTML response, with replaced placeholders.
   *
   * @see \Drupal\Core\Render\Renderer::replacePlaceholders()
   * @see \Drupal\Core\Render\Renderer::renderPlaceholder()
   */
  protected function renderPlaceholders(HtmlResponse $response) {
    $build = [
      '#markup' => SafeString::create($response->getContent()),
      '#attached' => $response->getAttachments(),
    ];
    // RendererInterface::renderRoot() renders the $build render array and
    // updates it in place. We don't care about the return value (which is just
    // $build['#markup']), but about the resulting render array.
    // @todo Simplify this when https://www.drupal.org/node/2495001 lands.
    $this->renderer->renderRoot($build);

    // Update the Response object now that the placeholders have been rendered.
    $placeholders_bubbleable_metadata = BubbleableMetadata::createFromRenderArray($build);
    $response
      ->setContent($build['#markup'])
      ->addCacheableDependency($placeholders_bubbleable_metadata)
      ->setAttachments($placeholders_bubbleable_metadata->getAttachments());

    return $response;
  }

  /**
   * Processes asset libraries into render arrays.
   *
   * @param array $attached
   *   The attachments to process.
   * @param array $placeholders
   *   The placeholders that exist in the response.
   *
   * @return array
   *   An array keyed by asset type, with keys:
   *     - styles
   *     - scripts
   *     - scripts_bottom
   */
  protected function processAssetLibraries(array $attached, array $placeholders) {
    $all_attached = ['#attached' => $attached];
    $assets = AttachedAssets::createFromRenderArray($all_attached);

    // Take Ajax page state into account, to allow for something like Turbolinks
    // to be implemented without altering core.
    // @see https://github.com/rails/turbolinks/
    // @todo https://www.drupal.org/node/2497115 - Below line is broken due to ->request.
    $ajax_page_state = $this->requestStack->getCurrentRequest()->request->get('ajax_page_state');
    $assets->setAlreadyLoadedLibraries(isset($ajax_page_state) ? explode(',', $ajax_page_state['libraries']) : []);

    $variables = [];

    // Print styles - if present.
    if (isset($placeholders['styles'])) {
      // Optimize CSS if necessary, but only during normal site operation.
      $optimize_css = !defined('MAINTENANCE_MODE') && $this->config->get('css.preprocess');
      $variables['styles'] = $this->cssCollectionRenderer->render($this->assetResolver->getCssAssets($assets, $optimize_css));
    }

    // Print scripts - if any are present.
    if (isset($placeholders['scripts']) || isset($placeholders['scripts_bottom'])) {
      // Optimize JS if necessary, but only during normal site operation.
      $optimize_js = !defined('MAINTENANCE_MODE') && !\Drupal::state()->get('system.maintenance_mode') && $this->config->get('js.preprocess');
      list($js_assets_header, $js_assets_footer) = $this->assetResolver->getJsAssets($assets, $optimize_js);
      $variables['scripts'] = $this->jsCollectionRenderer->render($js_assets_header);
      $variables['scripts_bottom'] = $this->jsCollectionRenderer->render($js_assets_footer);
    }

    return $variables;
  }

  /**
   * Renders HTML response attachment placeholders.
   *
   * @param \Drupal\Core\Render\HtmlResponse $response
   *   The HTML response to update.
   * @param array $placeholders
   *   An array of placeholders, keyed by type with the placeholders
   *   present in the content of the response as values.
   * @param array $variables
   *   The variables to render and replace, keyed by type with renderable
   *   arrays as values.
   */
  protected function renderHtmlResponseAttachmentPlaceholders(HtmlResponse $response, array $placeholders, array $variables) {
    $content = $response->getContent();
    foreach ($placeholders as $type => $placeholder) {
      if (isset($variables[$type])) {
        $content = str_replace($placeholder, $this->renderer->renderPlain($variables[$type]), $content);
      }
    }
    $response->setContent($content);
  }

  /**
   * Sets headers on a response object.
   *
   * @param \Drupal\Core\Render\HtmlResponse $response
   *   The HTML response to update.
   * @param array $headers
   *   The headers to set.
   */
  protected function setHeaders(HtmlResponse $response, array $headers) {
    foreach ($headers as $values) {
      $name = $values[0];
      $value = $values[1];
      $replace = !empty($values[2]);

      // Drupal treats the HTTP response status code like a header, even though
      // it really is not.
      if (strtolower($name) === 'status') {
        $response->setStatusCode($value);
      }
      $response->headers->set($name, $value, $replace);
    }
  }

}
