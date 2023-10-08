<?php

namespace Drupal\page_cache\StackMiddleware;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Executes the page caching before the main kernel takes over the request.
 */
class PageCache implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * A policy rule determining the cacheability of a request.
   *
   * @var \Drupal\Core\PageCache\RequestPolicyInterface
   */
  protected $requestPolicy;

  /**
   * A policy rule determining the cacheability of the response.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicyInterface
   */
  protected $responsePolicy;

  /**
   * The cache ID for the (master) request.
   *
   * @var string
   */
  protected $cid;

  /**
   * Constructs a PageCache object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $request_policy
   *   A policy rule determining the cacheability of a request.
   * @param \Drupal\Core\PageCache\ResponsePolicyInterface $response_policy
   *   A policy rule determining the cacheability of the response.
   */
  public function __construct(HttpKernelInterface $http_kernel, CacheBackendInterface $cache, RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy) {
    $this->httpKernel = $http_kernel;
    $this->cache = $cache;
    $this->requestPolicy = $request_policy;
    $this->responsePolicy = $response_policy;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    // Only allow page caching on master request.
    if ($type === static::MAIN_REQUEST && $this->requestPolicy->check($request) === RequestPolicyInterface::ALLOW) {
      $response = $this->lookup($request, $type, $catch);
    }
    else {
      $response = $this->pass($request, $type, $catch);
    }

    return $response;
  }

  /**
   * Sidesteps the page cache and directly forwards a request to the backend.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   * @param int $type
   *   The type of the request (one of HttpKernelInterface::MAIN_REQUEST or
   *   HttpKernelInterface::SUB_REQUEST)
   * @param bool $catch
   *   Whether to catch exceptions or not
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  protected function pass(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE) {
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Retrieves a response from the cache or fetches it from the backend.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   * @param int $type
   *   The type of the request (one of HttpKernelInterface::MAIN_REQUEST or
   *   HttpKernelInterface::SUB_REQUEST)
   * @param bool $catch
   *   Whether to catch exceptions or not
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  protected function lookup(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE) {
    if ($response = $this->get($request)) {
      $response->headers->set('X-Drupal-Cache', 'HIT');
    }
    else {
      $response = $this->fetch($request, $type, $catch);
    }

    // Only allow caching in the browser and prevent that the response is stored
    // by an external proxy server when the following conditions apply:
    // 1. There is a session cookie on the request.
    // 2. The Vary: Cookie header is on the response.
    // 3. The Cache-Control header does not contain the no-cache directive.
    if ($request->cookies->has(session_name()) &&
      in_array('Cookie', $response->getVary()) &&
      !$response->headers->hasCacheControlDirective('no-cache')) {

      $response->setPrivate();
    }

    // Perform HTTP revalidation.
    // @todo Use Response::isNotModified() as
    //   per https://www.drupal.org/node/2259489.
    $last_modified = $response->getLastModified();
    if ($last_modified) {
      // See if the client has provided the required HTTP headers.
      $if_modified_since = $request->server->has('HTTP_IF_MODIFIED_SINCE') ? strtotime($request->server->get('HTTP_IF_MODIFIED_SINCE')) : FALSE;
      $if_none_match = $request->server->has('HTTP_IF_NONE_MATCH') ? stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) : FALSE;

      if ($if_modified_since && $if_none_match
        // etag must match.
        && $if_none_match == $response->getEtag()
        // if-modified-since must match.
        && $if_modified_since == $last_modified->getTimestamp()) {
        $response->setStatusCode(304);
        $response->setContent(NULL);

        // In the case of a 304 response, certain headers must be sent, and the
        // remaining may not (see RFC 2616, section 10.3.5).
        foreach (array_keys($response->headers->all()) as $name) {
          if (!in_array($name, ['content-location', 'expires', 'cache-control', 'vary'])) {
            $response->headers->remove($name);
          }
        }
      }
    }

    return $response;
  }

  /**
   * Fetches a response from the backend and stores it in the cache.
   *
   * @see drupal_page_header()
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   * @param int $type
   *   The type of the request (one of HttpKernelInterface::MAIN_REQUEST or
   *   HttpKernelInterface::SUB_REQUEST)
   * @param bool $catch
   *   Whether to catch exceptions or not
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  protected function fetch(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE) {
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $response = $this->httpKernel->handle($request, $type, $catch);

    // Only set the 'X-Drupal-Cache' header if caching is allowed for this
    // response.
    if ($this->storeResponse($request, $response)) {
      $response->headers->set('X-Drupal-Cache', 'MISS');
    }

    return $response;
  }

  /**
   * Stores a response in the page cache.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   A response object that should be stored in the page cache.
   *
   * @return bool
   *   TRUE if the response has been stored successfully, FALSE otherwise.
   */
  protected function storeResponse(Request $request, Response $response) {
    // Drupal's primary cache invalidation architecture is cache tags: any
    // response that varies by a configuration value or data in a content
    // entity should have cache tags, to allow for instant cache invalidation
    // when that data is updated. However, HTTP does not standardize how to
    // encode cache tags in a response. Different CDNs implement their own
    // approaches, and configurable reverse proxies (e.g., Varnish) allow for
    // custom implementations. To keep Drupal's internal page cache simple, we
    // only cache CacheableResponseInterface responses, since those provide a
    // defined API for retrieving cache tags. For responses that do not
    // implement CacheableResponseInterface, there's no easy way to distinguish
    // responses that truly don't depend on any site data from responses that
    // contain invalidation information customized to a particular proxy or
    // CDN.
    // - Drupal modules are encouraged to use CacheableResponseInterface
    //   responses where possible and to leave the encoding of that information
    //   into response headers to the corresponding proxy/CDN integration
    //   modules.
    // - Custom applications that wish to provide internal page cache support
    //   for responses that do not implement CacheableResponseInterface may do
    //   so by replacing/extending this middleware service or adding another
    //   one.
    if (!$response instanceof CacheableResponseInterface) {
      return FALSE;
    }

    // Currently it is not possible to cache binary file or streamed responses:
    // https://github.com/symfony/symfony/issues/9128#issuecomment-25088678.
    // Therefore exclude them, even for subclasses that implement
    // CacheableResponseInterface.
    if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
      return FALSE;
    }

    // Allow policy rules to further restrict which responses to cache.
    if ($this->responsePolicy->check($response, $request) === ResponsePolicyInterface::DENY) {
      return FALSE;
    }

    $request_time = $request->server->get('REQUEST_TIME');
    // The response passes all of the above checks, so cache it. Page cache
    // entries default to Cache::PERMANENT since they will be expired via cache
    // tags locally. Because of this, page cache ignores max age.
    // - Get the tags from CacheableResponseInterface per the earlier comments.
    // - Get the time expiration from the Expires header, rather than the
    //   interface, but see https://www.drupal.org/node/2352009 about possibly
    //   changing that.
    $expire = 0;
    // 403 and 404 responses can fill non-LRU cache backends and generally are
    // likely to have a low cache hit rate. So do not cache them permanently.
    if ($response->isClientError()) {
      // Cache for an hour by default. If the 'cache_ttl_4xx' setting is
      // set to 0 then do not cache the response.
      $cache_ttl_4xx = Settings::get('cache_ttl_4xx', 3600);
      if ($cache_ttl_4xx > 0) {
        $expire = $request_time + $cache_ttl_4xx;
      }
    }
    // The getExpires method could return NULL if Expires header is not set, so
    // the returned value needs to be checked before calling getTimestamp.
    elseif ($expires = $response->getExpires()) {
      $date = $expires->getTimestamp();
      $expire = ($date > $request_time) ? $date : Cache::PERMANENT;
    }
    else {
      $expire = Cache::PERMANENT;
    }

    if ($expire === Cache::PERMANENT || $expire > $request_time) {
      $tags = $response->getCacheableMetadata()->getCacheTags();
      $this->set($request, $response, $expire, $tags);
    }

    return TRUE;
  }

  /**
   * Returns a response object from the page cache.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   * @param bool $allow_invalid
   *   (optional) If TRUE, a cache item may be returned even if it is expired or
   *   has been invalidated. Such items may sometimes be preferred, if the
   *   alternative is recalculating the value stored in the cache, especially
   *   if another concurrent request is already recalculating the same value.
   *   The "valid" property of the returned object indicates whether the item is
   *   valid or not. Defaults to FALSE.
   *
   * @return \Symfony\Component\HttpFoundation\Response|false
   *   The cached response or FALSE on failure.
   */
  protected function get(Request $request, $allow_invalid = FALSE) {
    $cid = $this->getCacheId($request);
    if ($cache = $this->cache->get($cid, $allow_invalid)) {
      return $cache->data;
    }
    return FALSE;
  }

  /**
   * Stores a response object in the page cache.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to store in the cache.
   * @param int $expire
   *   One of the following values:
   *   - CacheBackendInterface::CACHE_PERMANENT: Indicates that the item should
   *     not be removed unless it is deleted explicitly.
   *   - A Unix timestamp: Indicates that the item will be considered invalid
   *     after this time, i.e. it will not be returned by get() unless
   *     $allow_invalid has been set to TRUE. When the item has expired, it may
   *     be permanently deleted by the garbage collector at any time.
   * @param array $tags
   *   An array of tags to be stored with the cache item. These should normally
   *   identify objects used to build the cache item, which should trigger
   *   cache invalidation when updated. For example if a cached item represents
   *   a node, both the node ID and the author's user ID might be passed in as
   *   tags. For example array('node' => array(123), 'user' => array(92)).
   */
  protected function set(Request $request, Response $response, $expire, array $tags) {
    $cid = $this->getCacheId($request);
    $this->cache->set($cid, $response, $expire, $tags);
  }

  /**
   * Gets the page cache ID for this request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return string
   *   The cache ID for this request.
   */
  protected function getCacheId(Request $request) {
    // Once a cache ID is determined for the request, reuse it for the duration
    // of the request. This ensures that when the cache is written, it is only
    // keyed on request data that was available when it was read. For example,
    // the request format might be NULL during cache lookup and then set during
    // routing, in which case we want to key on NULL during writing, since that
    // will be the value during lookups for subsequent requests.
    if (!isset($this->cid)) {
      $cid_parts = [
        $request->getSchemeAndHttpHost() . $request->getRequestUri(),
        $request->getRequestFormat(NULL),
      ];
      $this->cid = implode(':', $cid_parts);
    }
    return $this->cid;
  }

}
