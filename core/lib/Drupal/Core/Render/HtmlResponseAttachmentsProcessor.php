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

    $attached = $response->getAttachments();

    // Get the placeholders from attached and then remove them.
    $placeholders = $attached['html_response_placeholders'];
    unset($attached['html_response_placeholders']);

    $variables = $this->processAssetLibraries($attached, $placeholders);

    // Handle all non-asset attachments. This populates drupal_get_html_head()
    // and drupal_get_http_header().
    $all_attached = ['#attached' => $attached];
    drupal_process_attached($all_attached);

    // Get HTML head elements - if present.
    if (isset($placeholders['head'])) {
      $variables['head'] = drupal_get_html_head(FALSE);
    }

    // Now replace the placeholders in the response content with the real data.
    $this->renderPlaceholders($response, $placeholders, $variables);

    // Finally set the headers on the response.
    $headers = drupal_get_http_header();
    $this->setHeaders($response, $headers);

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
      $optimize_js = !defined('MAINTENANCE_MODE') && $this->config->get('js.preprocess');
      list($js_assets_header, $js_assets_footer) = $this->assetResolver->getJsAssets($assets, $optimize_js);
      $variables['scripts'] = $this->jsCollectionRenderer->render($js_assets_header);
      $variables['scripts_bottom'] = $this->jsCollectionRenderer->render($js_assets_footer);
    }

    return $variables;
  }

  /**
   * Renders variables into HTML markup and replaces placeholders in the
   * response content.
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
  protected function renderPlaceholders(HtmlResponse $response, array $placeholders, array $variables) {
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
    foreach ($headers as $name => $value) {
      // Drupal treats the HTTP response status code like a header, even though
      // it really is not.
      if ($name === 'status') {
        $response->setStatusCode($value);
      }
      $response->headers->set($name, $value, FALSE);
    }
  }

}
