<?php

namespace Drupal\media\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\media\IFrameMarkup;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
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
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
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
   * @var \Drupal\Core\Logger\LoggerChannelInterface
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
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   */
  public function __construct(ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, RendererInterface $renderer, LoggerChannelInterface $logger, IFrameUrlHelper $iframe_url_helper) {
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
    $max_width = $request->query->getInt('max_width', NULL);
    $max_height = $request->query->getInt('max_height', NULL);

    // Hash the URL and max dimensions, and ensure it is equal to the hash
    // parameter passed in the query string.
    $hash = $this->iFrameUrlHelper->getHash($url, $max_width, $max_height);
    if (!Crypt::hashEquals($hash, $request->query->get('hash', ''))) {
      throw new AccessDeniedHttpException('This resource is not available');
    }

    // Return a response instead of a render array so that the frame content
    // will not have all the blocks and page elements normally rendered by
    // Drupal.
    $response = new CacheableResponse();
    $response->addCacheableDependency(Url::createFromRequest($request));

    try {
      $resource_url = $this->urlResolver->getResourceUrl($url, $max_width, $max_height);
      $resource = $this->resourceFetcher->fetchResource($resource_url);

      // Render the content in a new render context so that the cacheability
      // metadata of the rendered HTML will be captured correctly.
      $element = [
        '#theme' => 'media_oembed_iframe',
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
      ];
      $content = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($resource, $element) {
        return $this->renderer->render($element);
      });
      $response
        ->setContent($content)
        ->addCacheableDependency($resource)
        ->addCacheableDependency(CacheableMetadata::createFromRenderArray($element));
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
