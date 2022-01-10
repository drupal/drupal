<?php

namespace Drupal\media\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\media\IFrameMarkup;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller which renders an oEmbed resource in a bare page (without blocks).
 *
 * This controller is meant to render untrusted third-party HTML returned by
 * an oEmbed provider in an iframe, so as to mitigate the potential dangers of
 * of displaying third-party markup (i.e., XSS). The HTML returned by this
 * controller should not be trusted, and should *never* be displayed outside
 * of an iframe.
 *
 * @internal
 *   This is an internal part of the media system in Drupal core and may be
 *   subject to change in minor releases. This class should not be
 *   instantiated or extended by external code.
 */
class OEmbedIframeController implements ContainerInjectionInterface {

  /**
   * The oEmbed resource fetcher service.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * The oEmbed URL resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * Constructs an OEmbedIframeController instance.
   *
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   */
  public function __construct(ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, RendererInterface $renderer, LoggerInterface $logger, IFrameUrlHelper $iframe_url_helper) {
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->renderer = $renderer;
    $this->logger = $logger;
    $this->iFrameUrlHelper = $iframe_url_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('renderer'),
      $container->get('logger.factory')->get('media'),
      $container->get('media.oembed.iframe_url_helper')
    );
  }

  /**
   * Renders an oEmbed resource.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Will be thrown if the 'hash' parameter does not match the expected hash
   *   of the 'url' parameter.
   */
  public function render(Request $request) {
    $url = $request->query->get('url');
    $max_width = $request->query->getInt('max_width');
    $max_height = $request->query->getInt('max_height');

    // Hash the URL and max dimensions, and ensure it is equal to the hash
    // parameter passed in the query string.
    $hash = $this->iFrameUrlHelper->getHash($url, $max_width, $max_height);
    if (!hash_equals($hash, $request->query->get('hash', ''))) {
      throw new AccessDeniedHttpException('This resource is not available');
    }

    // Return a response instead of a render array so that the frame content
    // will not have all the blocks and page elements normally rendered by
    // Drupal.
    $response = new HtmlResponse('', HtmlResponse::HTTP_OK, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ]);
    $response->addCacheableDependency(Url::createFromRequest($request));

    try {
      $resource_url = $this->urlResolver->getResourceUrl($url, $max_width, $max_height);
      $resource = $this->resourceFetcher->fetchResource($resource_url);

      $placeholder_token = Crypt::randomBytesBase64(55);

      // Render the content in a new render context so that the cacheability
      // metadata of the rendered HTML will be captured correctly.
      $element = [
        '#theme' => 'media_oembed_iframe',
        '#resource' => $resource,
        // Even though the resource HTML is untrusted, IFrameMarkup::create()
        // will create a trusted string. The only reason this is okay is
        // because we are serving it in an iframe, which will mitigate the
        // potential dangers of displaying third-party markup.
        '#media' => IFrameMarkup::create($resource->getHtml()),
        '#cache' => [
          // Add the 'rendered' cache tag as this response is not processed by
          // \Drupal\Core\Render\MainContent\HtmlRenderer::renderResponse().
          'tags' => ['rendered'],
        ],
        '#attached' => [
          'html_response_attachment_placeholders' => [
            'styles' => '<css-placeholder token="' . $placeholder_token . '">',
          ],
          'library' => [
            'media/oembed.frame',
          ],
        ],
        '#placeholder_token' => $placeholder_token,
      ];
      $context = new RenderContext();
      $content = $this->renderer->executeInRenderContext($context, function () use ($element) {
        return $this->renderer->render($element);
      });
      $response
        ->setContent($content)
        ->setAttachments($element['#attached'])
        ->addCacheableDependency($resource)
        ->addCacheableDependency(CacheableMetadata::createFromRenderArray($element));

      // Modules and themes implementing hook_media_oembed_iframe_preprocess()
      // can add additional #cache and #attachments to a render array. If this
      // occurs, the render context won't be empty, and we need to ensure the
      // added metadata is bubbled up to the response.
      // @see \Drupal\Core\Theme\ThemeManager::render()
      if (!$context->isEmpty()) {
        $bubbleable_metadata = $context->pop();
        assert($bubbleable_metadata instanceof BubbleableMetadata);
        $response->addCacheableDependency($bubbleable_metadata);
        $response->addAttachments($bubbleable_metadata->getAttachments());
      }
    }
    catch (ResourceException $e) {
      // Prevent the response from being cached.
      $response->setMaxAge(0);

      // The oEmbed system makes heavy use of exception wrapping, so log the
      // entire exception chain to help with troubleshooting.
      do {
        // @todo Log additional information from ResourceException, to help with
        // debugging, in https://www.drupal.org/project/drupal/issues/2972846.
        $this->logger->error($e->getMessage());
        $e = $e->getPrevious();
      } while ($e);
    }

    return $response;
  }

}
