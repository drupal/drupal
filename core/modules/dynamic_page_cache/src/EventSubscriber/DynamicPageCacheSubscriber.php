<?php

namespace Drupal\dynamic_page_cache\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Cache\VariationCacheInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
   * The variation cache.
   *
   * @var \Drupal\Core\Cache\VariationCacheInterface
   */
  protected $cache;

  /**
   * The default cache contexts to vary every cache item by.
   *
   * @var string[]
   */
  protected $cacheContexts = [
    'route',
    // Some routes' controllers rely on the request format (they don't have
    // a separate route for each request format). Additionally, a controller
    // may be returning a domain object that a KernelEvents::VIEW subscriber
    // must turn into an actual response, but perhaps a format is being
    // requested that the subscriber does not support.
    // @see \Drupal\Core\EventSubscriber\RenderArrayNonHtmlSubscriber::onResponse()
    'request_format',
  ];

  /**
   * The cache contexts manager service.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextsManager;

  /**
   * The renderer configuration array.
   *
   * @var array
   */
  protected $rendererConfig;

  /**
   * Internal cache of request policy results.
   *
   * @var \SplObjectStorage
   */
  protected $requestPolicyResults;

  /**
   * Constructs a new DynamicPageCacheSubscriber object.
   *
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $request_policy
   *   A policy rule determining the cacheability of a request.
   * @param \Drupal\Core\PageCache\ResponsePolicyInterface $response_policy
   *   A policy rule determining the cacheability of the response.
   * @param \Drupal\Core\Cache\VariationCacheInterface $cache
   *   The variation cache.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager service.
   * @param array $renderer_config
   *   The renderer configuration array.
   */
  public function __construct(RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy, VariationCacheInterface $cache, CacheContextsManager $cache_contexts_manager, array $renderer_config) {
    $this->requestPolicy = $request_policy;
    $this->responsePolicy = $response_policy;
    $this->cache = $cache;
    $this->cacheContextsManager = $cache_contexts_manager;
    $this->rendererConfig = $renderer_config;
    $this->requestPolicyResults = new \SplObjectStorage();
  }

  /**
   * Sets a response in case of a Dynamic Page Cache hit.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequest(RequestEvent $event) {
    // Don't cache the response if the Dynamic Page Cache request policies are
    // not met. Store the result in a static keyed by current request, so that
    // onResponse() does not have to redo the request policy check.
    $request = $event->getRequest();
    $request_policy_result = $this->requestPolicy->check($request);
    $this->requestPolicyResults[$request] = $request_policy_result;
    if ($request_policy_result === RequestPolicyInterface::DENY) {
      return;
    }

    // Sets the response for the current route, if cached.
    $cached = $this->cache->get(['response'], (new CacheableMetadata())->setCacheContexts($this->cacheContexts));
    if ($cached) {
      $response = $cached->data;
      $response->headers->set(self::HEADER, 'HIT');
      $event->setResponse($response);
    }
  }

  /**
   * Stores a response in case of a Dynamic Page Cache miss, if cacheable.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event) {
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
    // Cache hit. This can happen when the master request is for example a 403
    // or 404, in which case a subrequest is performed by the router. In that
    // case, it is the subrequest's response that is cached by Dynamic Page
    // Cache, because the routing happens in a request subscriber earlier than
    // Dynamic Page Cache's and immediately sets a response, i.e. the one
    // returned by the subrequest, and thus causes Dynamic Page Cache's request
    // subscriber to not fire for the master request.
    // @see \Drupal\Core\Routing\AccessAwareRouter::checkAccess()
    // @see \Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber::on403()
    $request = $event->getRequest();
    if (!isset($this->requestPolicyResults[$request])) {
      return;
    }

    // Don't cache the response if the Dynamic Page Cache request & response
    // policies are not met.
    // @see onRequest()
    if ($this->requestPolicyResults[$request] === RequestPolicyInterface::DENY || $this->responsePolicy->check($response, $request) === ResponsePolicyInterface::DENY) {
      return;
    }

    $cacheable_metadata = CacheableMetadata::createFromObject($response->getCacheableMetadata());
    $this->cache->set(
      ['response'],
      $response,
      $cacheable_metadata->addCacheContexts($this->cacheContexts),
      (new CacheableMetadata())->setCacheContexts($this->cacheContexts)
    );

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
   * @param \Drupal\Core\Cache\CacheableResponseInterface $response
   *   The response whose cacheability to analyze.
   *
   * @return bool
   *   Whether the given response should be cached.
   *
   * @see \Drupal\Core\Render\Renderer::shouldAutomaticallyPlaceholder()
   */
  protected function shouldCacheResponse(CacheableResponseInterface $response) {
    $conditions = $this->rendererConfig['auto_placeholder_conditions'];

    // Create a new CacheableMetadata to avoid changing the response itself.
    $cacheability = CacheableMetadata::createFromObject($response->getCacheableMetadata());

    // Response's max-age is at or below the configured threshold.
    if ($cacheability->getCacheMaxAge() !== Cache::PERMANENT && $cacheability->getCacheMaxAge() <= $conditions['max-age']) {
      return FALSE;
    }

    // Optimize the contexts and let them affect the cache tags to mimic what
    // happens to the cacheability in the variation cache.
    $cacheability->addCacheableDependency($this->cacheContextsManager->convertTokensToKeys($cacheability->getCacheContexts()));
    $cacheability->setCacheContexts($this->cacheContextsManager->optimizeTokens($cacheability->getCacheContexts()));

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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];

    // Run after AuthenticationSubscriber (necessary for the 'user' cache
    // context; priority 300) and MaintenanceModeSubscriber (Dynamic Page Cache
    // should not be polluted by maintenance mode-specific behavior; priority
    // 30), but before ContentControllerSubscriber (updates _controller, but
    // that is a no-op when Dynamic Page Cache runs; priority 25).
    $events[KernelEvents::REQUEST][] = ['onRequest', 27];

    // Run before HtmlResponseSubscriber::onRespond(), which has priority 0.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 100];

    return $events;
  }

}
