<?php

namespace Drupal\media\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
   */
  public function __construct(ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, RendererInterface $renderer, LoggerChannelInterface $logger) {
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('renderer'),
      $container->get('logger.factory')->get('media')
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
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Will be thrown when the 'url' parameter is not specified, invalid, or not
   *   external.
   */
  public function render(Request $request) {
    $url = $request->query->get('url');
    if (!$url) {
      throw new BadRequestHttpException('url parameter not provided');
    }
    if (!UrlHelper::isValid($url, TRUE)) {
      throw new BadRequestHttpException('url parameter is invalid');
    }
    if (!UrlHelper::isExternal($url)) {
      throw new BadRequestHttpException('url parameter is not external');
    }

    // Return a response instead of a render array so that the frame content
    // will not have all the blocks and page elements normally rendered by
    // Drupal.
    $response = new CacheableResponse();
    $response->addCacheableDependency(Url::createFromRequest($request));

    try {
      $resource_url = $this->urlResolver->getResourceUrl($url, $request->query->getInt('max_width', NULL), $request->query->getInt('max_height', NULL));
      $resource = $this->resourceFetcher->fetchResource($resource_url);

      // Render the content in a new render context so that the cacheability
      // metadata of the rendered HTML will be captured correctly.
      $content = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($resource) {
        $element = [
          '#theme' => 'media_oembed',
          // Even though the resource HTML is untrusted, Markup::create() will
          // create a trusted string. The only reason this is okay is because
          // we are serving it in an iframe, which will mitigate the potential
          // dangers of displaying third-party markup.
          '#post' => Markup::create($resource->getHtml()),
        ];
        return $this->renderer->render($element);
      });

      $response->setContent($content)->addCacheableDependency($resource);
    }
    catch (ResourceException $e) {
      // Prevent the response from being cached.
      $response->setMaxAge(0);
      // @todo Log additional information from ResourceException, to help with
      // debugging, in https://www.drupal.org/project/drupal/issues/2972846.
      $this->logger->error($e->getMessage());
    }

    return $response;
  }

}
