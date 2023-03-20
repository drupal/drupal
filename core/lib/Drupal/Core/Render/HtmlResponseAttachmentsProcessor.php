<?php

namespace Drupal\Core\Render;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes attachments of HTML responses.
 *
 * This class is used by the rendering service to process the #attached part of
 * the render array, for HTML responses.
 *
 * To render attachments to HTML for testing without a controller, use the
 * 'bare_html_page_renderer' service to generate a
 * Drupal\Core\Render\HtmlResponse object. Then use its getContent(),
 * getStatusCode(), and/or the headers property to access the result.
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
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface|null $languageManager
   *   The language manager.
   */
  public function __construct(AssetResolverInterface $asset_resolver, ConfigFactoryInterface $config_factory, AssetCollectionRendererInterface $css_collection_renderer, AssetCollectionRendererInterface $js_collection_renderer, RequestStack $request_stack, RendererInterface $renderer, ModuleHandlerInterface $module_handler, protected ?LanguageManagerInterface $languageManager = NULL) {
    $this->assetResolver = $asset_resolver;
    $this->config = $config_factory->get('system.performance');
    $this->cssCollectionRenderer = $css_collection_renderer;
    $this->jsCollectionRenderer = $js_collection_renderer;
    $this->requestStack = $request_stack;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    if (!isset($languageManager)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $languageManager argument is deprecated in drupal:10.1.0 and will be required in drupal:11.0.0', E_USER_DEPRECATED);
      $this->languageManager = \Drupal::languageManager();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    assert($response instanceof HtmlResponse);

    // First, render the actual placeholders; this may cause additional
    // attachments to be added to the response, which the attachment
    // placeholders rendered by renderHtmlResponseAttachmentPlaceholders() will
    // need to include.
    //
    // @todo Exceptions should not be used for code flow control. However, the
    //   Form API does not integrate with the HTTP Kernel based architecture of
    //   Drupal 8. In order to resolve this issue properly it is necessary to
    //   completely separate form submission from rendering.
    //   @see https://www.drupal.org/node/2367555
    try {
      $response = $this->renderPlaceholders($response);
    }
    catch (EnforcedResponseException $e) {
      return $e->getResponse();
    }

    // Get a reference to the attachments.
    $attached = $response->getAttachments();

    // Send a message back if the render array has unsupported #attached types.
    $unsupported_types = array_diff(
      array_keys($attached),
      ['html_head', 'feed', 'html_head_link', 'http_header', 'library', 'html_response_attachment_placeholders', 'placeholders', 'drupalSettings']
    );
    if (!empty($unsupported_types)) {
      throw new \LogicException(sprintf('You are not allowed to use %s in #attached.', implode(', ', $unsupported_types)));
    }

    // If we don't have any placeholders, there is no need to proceed.
    if (!empty($attached['html_response_attachment_placeholders'])) {
      // Get the placeholders from attached and then remove them.
      $attachment_placeholders = $attached['html_response_attachment_placeholders'];
      unset($attached['html_response_attachment_placeholders']);

      $assets = AttachedAssets::createFromRenderArray(['#attached' => $attached]);
      // Take Ajax page state into account, to allow for something like
      // Turbolinks to be implemented without altering core.
      // @see https://github.com/rails/turbolinks/
      $ajax_page_state = $this->requestStack->getCurrentRequest()->get('ajax_page_state');
      $assets->setAlreadyLoadedLibraries(isset($ajax_page_state) ? explode(',', $ajax_page_state['libraries']) : []);
      $variables = $this->processAssetLibraries($assets, $attachment_placeholders);
      // $variables now contains the markup to load the asset libraries. Update
      // $attached with the final list of libraries and JavaScript settings, so
      // that $response can be updated with those. Then the response object will
      // list the final, processed attachments.
      $attached['library'] = $assets->getLibraries();
      $attached['drupalSettings'] = $assets->getSettings();

      // Since we can only replace content in the HTML head section if there's a
      // placeholder for it, we can safely avoid processing the render array if
      // it's not present.
      if (!empty($attachment_placeholders['head'])) {
        // 'feed' is a special case of 'html_head_link'. We process them into
        // 'html_head_link' entries and merge them.
        if (!empty($attached['feed'])) {
          $attached = BubbleableMetadata::mergeAttachments(
            $attached,
            $this->processFeed($attached['feed'])
          );
          unset($attached['feed']);
        }
        // 'html_head_link' is a special case of 'html_head' which can be present
        // as a head element, but also as a Link: HTTP header depending on
        // settings in the render array. Processing it can add to both the
        // 'html_head' and 'http_header' keys of '#attached', so we must address
        // it before 'html_head'.
        if (!empty($attached['html_head_link'])) {
          // Merge the processed 'html_head_link' into $attached so that its
          // 'html_head' and 'http_header' values are present for further
          // processing.
          $attached = BubbleableMetadata::mergeAttachments(
            $attached,
            $this->processHtmlHeadLink($attached['html_head_link'])
          );
          unset($attached['html_head_link']);
        }

        // Now we can process 'html_head', which contains both 'feed' and
        // 'html_head_link'.
        if (!empty($attached['html_head'])) {
          $variables['head'] = $this->processHtmlHead($attached['html_head']);
        }
      }

      // Now replace the attachment placeholders.
      $this->renderHtmlResponseAttachmentPlaceholders($response, $attachment_placeholders, $variables);
    }

    // Set the HTTP headers and status code on the response if any bubbled.
    if (!empty($attached['http_header'])) {
      $this->setHeaders($response, $attached['http_header']);
    }

    // AttachmentsResponseProcessorInterface mandates that the response it
    // processes contains the final attachment values.
    $response->setAttachments($attached);

    return $response;
  }

  /**
   * Formats an attribute string for an HTTP header.
   *
   * @param array $attributes
   *   An associative array of attributes such as 'rel'.
   *
   * @return string
   *   A ; separated string ready for insertion in a HTTP header. No escaping is
   *   performed for HTML entities, so this string is not safe to be printed.
   *
   * @internal
   *
   * @see https://www.drupal.org/node/3000051
   */
  public static function formatHttpHeaderAttributes(array $attributes = []) {
    foreach ($attributes as $attribute => &$data) {
      if (is_array($data)) {
        $data = implode(' ', $data);
      }
      $data = $attribute . '="' . $data . '"';
    }
    return $attributes ? ' ' . implode('; ', $attributes) : '';
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
      '#markup' => Markup::create($response->getContent()),
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
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   The attached assets collection for the current response.
   * @param array $placeholders
   *   The placeholders that exist in the response.
   *
   * @return array
   *   An array keyed by asset type, with keys:
   *     - styles
   *     - scripts
   *     - scripts_bottom
   */
  protected function processAssetLibraries(AttachedAssetsInterface $assets, array $placeholders) {
    $variables = [];

    // Print styles - if present.
    if (isset($placeholders['styles'])) {
      // Optimize CSS if necessary, but only during normal site operation.
      $optimize_css = !defined('MAINTENANCE_MODE') && $this->config->get('css.preprocess');
      $variables['styles'] = $this->cssCollectionRenderer->render($this->assetResolver->getCssAssets($assets, $optimize_css, $this->languageManager->getCurrentLanguage()));
    }

    // Print scripts - if any are present.
    if (isset($placeholders['scripts']) || isset($placeholders['scripts_bottom'])) {
      // Optimize JS if necessary, but only during normal site operation.
      $optimize_js = !defined('MAINTENANCE_MODE') && !\Drupal::state()->get('system.maintenance_mode') && $this->config->get('js.preprocess');
      [$js_assets_header, $js_assets_footer] = $this->assetResolver->getJsAssets($assets, $optimize_js, $this->languageManager->getCurrentLanguage());
      $variables['scripts'] = $this->jsCollectionRenderer->render($js_assets_header);
      $variables['scripts_bottom'] = $this->jsCollectionRenderer->render($js_assets_footer);
    }

    return $variables;
  }

  /**
   * Renders HTML response attachment placeholders.
   *
   * This is the last step where all of the attachments are placed into the
   * response object's contents.
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
   *   The headers to set, as an array. The items in this array should be as
   *   follows:
   *   - The header name.
   *   - The header value.
   *   - (optional) Whether to replace a current value with the new one, or add
   *     it to the others. If the value is not replaced, it will be appended,
   *     resulting in a header like this: 'Header: value1,value2'
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
      else {
        $response->headers->set($name, $value, $replace);
      }
    }
  }

  /**
   * Ensure proper key/data order and defaults for renderable head items.
   *
   * @param array $html_head
   *   The ['#attached']['html_head'] portion of a render array.
   *
   * @return array
   *   The ['#attached']['html_head'] portion of a render array with #type of
   *   html_tag added for items without a #type.
   */
  protected function processHtmlHead(array $html_head) {
    $head = [];
    foreach ($html_head as $item) {
      [$data, $key] = $item;
      if (!isset($data['#type'])) {
        $data['#type'] = 'html_tag';
      }
      $head[$key] = $data;
    }
    return $head;
  }

  /**
   * Transform a html_head_link array into html_head and http_header arrays.
   *
   * Variable html_head_link is a special case of html_head which can be present
   * as a link item in the HTML head section, and also as a Link: HTTP header,
   * depending on options in the render array. Processing it can add to both the
   * html_head and http_header sections.
   *
   * @param array $html_head_link
   *   The 'html_head_link' value of a render array. Each head link is specified
   *   by a two-element array:
   *   - An array specifying the attributes of the link. The 'href' and 'rel'
   *     attributes are required, and the 'href' attribute is expected to be a
   *     percent-encoded URI for proper serialization in the Link: HTTP header,
   *     as specified by RFC 8288.
   *   - A boolean specifying whether the link should also be a Link: HTTP
   *     header.
   *
   * @return array
   *   An ['#attached'] section of a render array. This allows us to easily
   *   merge the results with other render arrays. The array could contain the
   *   following keys:
   *   - http_header
   *   - html_head
   */
  protected function processHtmlHeadLink(array $html_head_link) {
    $attached = [];

    foreach ($html_head_link as $item) {
      $attributes = $item[0];
      $should_add_header = $item[1] ?? FALSE;

      $element = [
        '#tag' => 'link',
        '#attributes' => $attributes,
      ];
      $href = $attributes['href'];
      $rel = $attributes['rel'];

      // Allow multiple hreflang tags to use the same href.
      if (isset($attributes['hreflang'])) {
        $attached['html_head'][] = [$element, 'html_head_link:' . $rel . ':' . $attributes['hreflang'] . ':' . $href];
      }
      else {
        $attached['html_head'][] = [$element, 'html_head_link:' . $rel . ':' . $href];
      }

      if ($should_add_header) {
        // Also add a HTTP header "Link:".
        $href = '<' . $attributes['href'] . '>';
        unset($attributes['href']);
        if ($param = static::formatHttpHeaderAttributes($attributes)) {
          $href .= ';' . $param;
        }

        $attached['http_header'][] = ['Link', $href, FALSE];
      }
    }
    return $attached;
  }

  /**
   * Transform a 'feed' attachment into an 'html_head_link' attachment.
   *
   * The RSS feed is a special case of 'html_head_link', so we just turn it into
   * one.
   *
   * @param array $attached_feed
   *   The ['#attached']['feed'] portion of a render array.
   *
   * @return array
   *   An ['#attached']['html_head_link'] array, suitable for merging with
   *   another 'html_head_link' array.
   */
  protected function processFeed($attached_feed) {
    $html_head_link = [];
    foreach ($attached_feed as $item) {
      $feed_link = [
        'href' => $item[0],
        'rel' => 'alternate',
        'title' => empty($item[1]) ? '' : $item[1],
        'type' => 'application/rss+xml',
      ];
      $html_head_link[] = [$feed_link, FALSE];
    }
    return ['html_head_link' => $html_head_link];
  }

}
