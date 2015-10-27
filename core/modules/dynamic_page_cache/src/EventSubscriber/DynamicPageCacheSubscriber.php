<?php

/**
 * @file
 * Contains \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber.
 */

namespace Drupal\dynamic_page_cache\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RenderCacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Returns cached responses as early and avoiding as much work as possible.
 *
 * Dynamic Page Cache is able to cache so much because it utilizes cache
 * contexts: the cache contexts that are present capture the variations of every
 * component of the page. That, combined with the fact that cacheability
 * metadata is bubbled, means that the cache contexts at the page level
 * represent the complete set of contexts that the page varies by.
 *
 * The reason Dynamic Page Cache is implemented as two event subscribers (a late
 * REQUEST subscriber immediately after routing for cache hits, and an early
 * RESPONSE subscriber for cache misses) is because many cache contexts can only
 * be evaluated after routing. (Examples: 'user', 'user.permissions', 'route' …)
 * Consequently, it is impossible to implement Dynamic Page Cache as a kernel
 * middleware that simply caches per URL.
 *
 * @see \Drupal\Core\Render\MainContent\HtmlRenderer
 * @see \Drupal\Core\Cache\CacheableResponseInterface
 */
class DynamicPageCacheSubscriber implements EventSubscriberInterface {

  /**
   * Attribute name of the Dynamic Page Cache request policy result.
   *
   * @see onRouteMatch()
   * @see onRespond()
   */
  const ATTRIBUTE_REQUEST_POLICY_RESULT = '_dynamic_page_cache_request_policy_result';

  /**
   * Name of Dynamic Page Cache's response header.
   */
  const HEADER = 'X-Drupal-Dynamic-Cache';

  /**
   * A request policy rule determining the cacheability of a response.
   *
   * @var \Drupal\Core\PageCache\RequestPolicyInterface
   */
  protected $requestPolicy;

  /**
   * A response policy rule determining the cacheability of the response.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicyInterface
   */
  protected $responsePolicy;

  /**
   * The render cache.
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
   * Dynamic Page Cache's redirect render array.
   *
   * @var array
   */
  protected $dynamicPageCacheRedirectRenderArray = [
    '#cache' => [
      'keys' => ['response'],
      'contexts' => [
        'route',
        // Some routes' controllers rely on the request format (they don't have
        // a separate route for each request format). Additionally, a controller
        // may be returning a domain object that a KernelEvents::VIEW subscriber
        // must turn into an actual response, but perhaps a format is being
        // requested that the subscriber does not support.
        // @see \Drupal\Core\EventSubscriber\AcceptNegotiation406::onViewDetect406()
        'request_format',
      ],
      'bin' => 'dynamic_page_cache',
    ],
  ];

  /**
   * Constructs a new DynamicPageCacheSubscriber object.
   *
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $request_policy
   *   A policy rule determining the cacheability of a request.
   * @param \Drupal\Core\PageCache\ResponsePolicyInterface $response_policy
   *   A policy rule determining the cacheability of the response.
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache.
   * @param array $renderer_config
   *   The renderer configuration array.
   */
  public function __construct(RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy, RenderCacheInterface $render_cache, array $renderer_config) {
    $this->requestPolicy = $request_policy;
    $this->responsePolicy = $response_policy;
    $this->renderCache = $render_cache;
    $this->rendererConfig = $renderer_config;
  }

  /**
   * Sets a response in case of a Dynamic Page Cache hit.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRouteMatch(GetResponseEvent $event) {
    // Don't cache the response if the Dynamic Page Cache request policies are
    // not met. Store the result in a request attribute, so that onResponse()
    // does not have to redo the request policy check.
    $request = $event->getRequest();
    $request_policy_result = $this->requestPolicy->check($request);
    $request->attributes->set(self::ATTRIBUTE_REQUEST_POLICY_RESULT, $request_policy_result);
    if ($request_policy_result === RequestPolicyInterface::DENY) {
      return;
    }

    // Sets the response for the current route, if cached.
    $cached = $this->renderCache->get($this->dynamicPageCacheRedirectRenderArray);
    if ($cached) {
      $response = $this->renderArrayToResponse($cached);
      $response->headers->set(self::HEADER, 'HIT');
      $event->setResponse($response);
    }
  }

  /**
   * Stores a response in case of a Dynamic Page Cache miss, if cacheable.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    // Dynamic Page Cache only works with cacheable responses. It does not work
    // with plain Response objects. (Dynamic Page Cache needs to be able to
    // access and modify the cacheability metadata associated with the
    // response.)
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    // There's no work left to be done if this is a Dynamic Page Cache hit.
    if ($response->headers->get(self::HEADER) === 'HIT') {
      return;
    }

    // There's no work left to be done if this is an uncacheable response.
    if (!$this->shouldCacheResponse($response)) {
      // The response is uncacheable, mark it as such.
      $response->headers->set(self::HEADER, 'UNCACHEABLE');
      return;
    }

    // Don't cache the response if Dynamic Page Cache's request subscriber did
    // not fire, because that means it is impossible to have a Dynamic Page
    // Cache hit. (This can happen when the master request is for example a 403
    // or 404, in which case a subrequest is performed by the router. In that
    // case, it is the subrequest's response that is cached by Dynamic Page
    // Cache, because the routing happens in a request subscriber earlier than
    // Dynamic Page Cache's and immediately sets a response, i.e. the one
    // returned by the subrequest, and thus causes Dynamic Page Cache's request
    // subscriber to not fire for the master request.)
    // @see \Drupal\Core\Routing\AccessAwareRouter::checkAccess()
    // @see \Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber::on403()
    $request = $event->getRequest();
    if (!$request->attributes->has(self::ATTRIBUTE_REQUEST_POLICY_RESULT)) {
      return;
    }

    // Don't cache the response if the Dynamic Page Cache request & response
    // policies are not met.
    // @see onRouteMatch()
    if ($request->attributes->get(self::ATTRIBUTE_REQUEST_POLICY_RESULT) === RequestPolicyInterface::DENY || $this->responsePolicy->check($response, $request) === ResponsePolicyInterface::DENY) {
      return;
    }

    // Embed the response object in a render array so that RenderCache is able
    // to cache it, handling cache redirection for us.
    $response_as_render_array = $this->responseToRenderArray($response);
    $this->renderCache->set($response_as_render_array, $this->dynamicPageCacheRedirectRenderArray);

    // The response was generated, mark the response as a cache miss. The next
    // time, it will be a cache hit.
    $response->headers->set(self::HEADER, 'MISS');
  }

  /**
   * Whether the given response should be cached by Dynamic Page Cache.
   *
   * We consider any response that has cacheability metadata meeting the auto-
   * placeholdering conditions to be uncacheable. Because those conditions
   * indicate poor cacheability, and if it doesn't make sense to cache parts of
   * a page, then neither does it make sense to cache an entire page.
   *
   * But note that auto-placeholdering avoids such cacheability metadata ever
   * bubbling to the response level: while rendering, the Renderer checks every
   * subtree to see if meets the auto-placeholdering conditions. If it does, it
   * is automatically placeholdered, and consequently the cacheability metadata
   * of the placeholdered content does not bubble up to the response level.
   *
   * @param \Drupal\Core\Cache\CacheableResponseInterface
   *   The response whose cacheability to analyze.
   *
   * @return bool
   *   Whether the given response should be cached.
   *
   * @see \Drupal\Core\Render\Renderer::shouldAutomaticallyPlaceholder()
   */
  protected function shouldCacheResponse(CacheableResponseInterface $response) {
    $conditions = $this->rendererConfig['auto_placeholder_conditions'];

    $cacheability = $response->getCacheableMetadata();

    // Response's max-age is at or below the configured threshold.
    if ($cacheability->getCacheMaxAge() !== Cache::PERMANENT && $cacheability->getCacheMaxAge() <= $conditions['max-age']) {
      return FALSE;
    }

    // Response has a high-cardinality cache context.
    if (array_intersect($cacheability->getCacheContexts(), $conditions['contexts'])) {
      return FALSE;
    }

    // Response has a high-invalidation frequency cache tag.
    if (array_intersect($cacheability->getCacheTags(), $conditions['tags'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Embeds a Response object in a render array so that RenderCache can cache it.
   *
   * @param \Drupal\Core\Cache\CacheableResponseInterface $response
   *   A cacheable response.
   *
   * @return array
   *   A render array that embeds the given cacheable response object, with the
   *   cacheability metadata of the response object present in the #cache
   *   property of the render array.
   *
   * @see renderArrayToResponse()
   *
   * @todo Refactor/remove once https://www.drupal.org/node/2551419 lands.
   */
  protected function responseToRenderArray(CacheableResponseInterface $response) {
    $response_as_render_array = $this->dynamicPageCacheRedirectRenderArray + [
      // The data we actually care about.
      '#response' => $response,
      // Tell RenderCache to cache the #response property: the data we actually
      // care about.
      '#cache_properties' => ['#response'],
      // These exist only to fulfill the requirements of the RenderCache, which
      // is designed to work with render arrays only. We don't care about these.
      '#markup' => '',
      '#attached' => '',
    ];

    // Merge the response's cacheability metadata, so that RenderCache can take
    // care of cache redirects for us.
    CacheableMetadata::createFromObject($response->getCacheableMetadata())
      ->merge(CacheableMetadata::createFromRenderArray($response_as_render_array))
      ->applyTo($response_as_render_array);

    return $response_as_render_array;
  }

  /**
   * Gets the embedded Response object in a render array.
   *
   * @param array $render_array
   *   A render array with a #response property.
   *
   * @return \Drupal\Core\Cache\CacheableResponseInterface
   *   The cacheable response object.
   *
   * @see responseToRenderArray()
   */
  protected function renderArrayToResponse(array $render_array) {
    return $render_array['#response'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    // Run after AuthenticationSubscriber (necessary for the 'user' cache
    // context; priority 300) and MaintenanceModeSubscriber (Dynamic Page Cache
    // should not be polluted by maintenance mode-specific behavior; priority
    // 30), but before ContentControllerSubscriber (updates _controller, but
    // that is a no-op when Dynamic Page Cache runs; priority 25).
    $events[KernelEvents::REQUEST][] = ['onRouteMatch', 27];

    // Run before HtmlResponseSubscriber::onRespond(), which has priority 0.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 100];

    return $events;
  }

}
