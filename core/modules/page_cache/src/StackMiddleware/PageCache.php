<?php

/**
 * @file
 * Contains \Drupal\page_cache\StackMiddleware\PageCache.
 */

namespace Drupal\page_cache\StackMiddleware;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
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
   * @var \Drupal\Core\Cache\CacheBackendInterface.
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
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    // Only allow page caching on master request.
    if ($type === static::MASTER_REQUEST && $this->requestPolicy->check($request) === RequestPolicyInterface::ALLOW) {
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
   *   The type of the request (one of HttpKernelInterface::MASTER_REQUEST or
   *   HttpKernelInterface::SUB_REQUEST)
   * @param bool $catch
   *   Whether to catch exceptions or not
   *
   * @returns \Symfony\Component\HttpFoundation\Response $response
   *   A response object.
   */
  protected function pass(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Retrieves a response from the cache or fetches it from the backend.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   * @param int $type
   *   The type of the request (one of HttpKernelInterface::MASTER_REQUEST or
   *   HttpKernelInterface::SUB_REQUEST)
   * @param bool $catch
   *   Whether to catch exceptions or not
   *
   * @returns \Symfony\Component\HttpFoundation\Response $response
   *   A response object.
   */
  protected function lookup(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
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

    // Negotiate whether to use compression.
    if (extension_loaded('zlib') && $response->headers->get('Content-Encoding') === 'gzip') {
      if (strpos($request->headers->get('Accept-Encoding'), 'gzip') !== FALSE) {
        // The response content is already gzip'ed, so make sure
        // zlib.output_compression does not compress it once more.
        ini_set('zlib.output_compression', '0');
      }
      else {
        // The client does not support compression. Decompress the content and
        // remove the Content-Encoding header.
        $content = $response->getContent();
        $content = gzinflate(substr(substr($content, 10), 0, -8));
        $response->setContent($content);
        $response->headers->remove('Content-Encoding');
      }
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
        && $if_none_match == $response->getEtag() // etag must match
        && $if_modified_since == $last_modified->getTimestamp()) {  // if-modified-since must match
        $response->setStatusCode(304);
        $response->setContent(NULL);

        // In the case of a 304 response, certain headers must be sent, and the
        // remaining may not (see RFC 2616, section 10.3.5).
        foreach (array_keys($response->headers->all()) as $name) {
          if (!in_array($name, array('content-location', 'expires', 'cache-control', 'vary'))) {
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
   * If page_compression is enabled, a gzipped version of the page is stored in
   * the cache to avoid compressing the output on each request. The cache entry
   * is unzipped in the relatively rare event that the page is requested by a
   * client without gzip support.
   *
   * Page compression requires the PHP zlib extension
   * (http://php.net/manual/ref.zlib.php).
   *
   * @see drupal_page_header()
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   * @param int $type
   *   The type of the request (one of HttpKernelInterface::MASTER_REQUEST or
   *   HttpKernelInterface::SUB_REQUEST)
   * @param bool $catch
   *   Whether to catch exceptions or not
   *
   * @returns \Symfony\Component\HttpFoundation\Response $response
   *   A response object.
   */
  protected function fetch(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $response = $this->httpKernel->handle($request, $type, $catch);

    // Currently it is not possible to cache some types of responses. Therefore
    // exclude binary file responses (generated files, e.g. images with image
    // styles) and streamed responses (files directly read from the disk).
    // see: https://github.com/symfony/symfony/issues/9128#issuecomment-25088678
    if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
      return $response;
    }

    if ($this->responsePolicy->check($response, $request) === ResponsePolicyInterface::DENY) {
      return $response;
    }

    // Use the actual timestamp from an Expires header, if available.
    $date = $response->getExpires()->getTimestamp();
    $expire = ($date > time()) ? $date : Cache::PERMANENT;

    $tags = explode(' ', $response->headers->get('X-Drupal-Cache-Tags'));
    $this->set($request, $response, $expire, $tags);

    // Mark response as a cache miss.
    $response->headers->set('X-Drupal-Cache', 'MISS');

    return $response;
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
    $cid_parts = array(
      $request->getUri(),
      $request->getRequestFormat(),
    );
    return implode(':', $cid_parts);
  }

}
