<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\FinishResponseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to handle finished responses.
 */
class FinishResponseSubscriber implements EventSubscriberInterface {

  /**
   * The language manager object for retrieving the correct language code.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

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
   * The cache contexts manager service.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContexts;

  /**
   * Constructs a FinishResponseSubscriber object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager object for retrieving the correct language code.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $request_policy
   *   A policy rule determining the cacheability of a request.
   * @param \Drupal\Core\PageCache\ResponsePolicyInterface $response_policy
   *   A policy rule determining the cacheability of a response.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager service.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy, CacheContextsManager $cache_contexts_manager) {
    $this->languageManager = $language_manager;
    $this->config = $config_factory->get('system.performance');
    $this->requestPolicy = $request_policy;
    $this->responsePolicy = $response_policy;
    $this->cacheContextsManager = $cache_contexts_manager;
  }

  /**
   * Sets extra headers on successful responses.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();
    $response = $event->getResponse();

    // Set the X-UA-Compatible HTTP header to force IE to use the most recent
    // rendering engine.
    $response->headers->set('X-UA-Compatible', 'IE=edge', FALSE);

    // Set the Content-language header.
    $response->headers->set('Content-language', $this->languageManager->getCurrentLanguage()->getId());

    // Prevent browsers from sniffing a response and picking a MIME type
    // different from the declared content-type, since that can lead to
    // XSS and other vulnerabilities.
    // https://www.owasp.org/index.php/List_of_useful_HTTP_headers
    $response->headers->set('X-Content-Type-Options', 'nosniff', FALSE);
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN', FALSE);

    // Expose the cache contexts and cache tags associated with this page in a
    // X-Drupal-Cache-Contexts and X-Drupal-Cache-Tags header respectively.
    if ($response instanceof CacheableResponseInterface) {
      $response_cacheability = $response->getCacheableMetadata();
      $response->headers->set('X-Drupal-Cache-Tags', implode(' ', $response_cacheability->getCacheTags()));
      $response->headers->set('X-Drupal-Cache-Contexts', implode(' ', $this->cacheContextsManager->optimizeTokens($response_cacheability->getCacheContexts())));
    }

    $is_cacheable = ($this->requestPolicy->check($request) === RequestPolicyInterface::ALLOW) && ($this->responsePolicy->check($response, $request) !== ResponsePolicyInterface::DENY);

    // Add headers necessary to specify whether the response should be cached by
    // proxies and/or the browser.
    if ($is_cacheable && $this->config->get('cache.page.max_age') > 0) {
      if (!$this->isCacheControlCustomized($response)) {
        // Only add the default Cache-Control header if the controller did not
        // specify one on the response.
        $this->setResponseCacheable($response, $request);
      }
    }
    else {
      // If either the policy forbids caching or the sites configuration does
      // not allow to add a max-age directive, then enforce a Cache-Control
      // header declaring the response as not cacheable.
      $this->setResponseNotCacheable($response, $request);
    }
  }

  /**
   * Determine whether the given response has a custom Cache-Control header.
   *
   * Upon construction, the ResponseHeaderBag is initialized with an empty
   * Cache-Control header. Consequently it is not possible to check whether the
   * header was set explicitly by simply checking its presence. Instead, it is
   * necessary to examine the computed Cache-Control header and compare with
   * values known to be present only when Cache-Control was never set
   * explicitly.
   *
   * When neither Cache-Control nor any of the ETag, Last-Modified, Expires
   * headers are set on the response, ::get('Cache-Control') returns the value
   * 'no-cache'. If any of ETag, Last-Modified or Expires are set but not
   * Cache-Control, then 'private, must-revalidate' (in exactly this order) is
   * returned.
   *
   * @see \Symfony\Component\HttpFoundation\ResponseHeaderBag::computeCacheControlValue()
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *
   * @return bool
   *   TRUE when Cache-Control header was set explicitly on the given response.
   */
  protected function isCacheControlCustomized(Response $response) {
    $cache_control = $response->headers->get('Cache-Control');
    return $cache_control != 'no-cache' && $cache_control != 'private, must-revalidate';
  }

  /**
   * Add Cache-Control and Expires headers to a response which is not cacheable.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   A response object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   */
  protected function setResponseNotCacheable(Response $response, Request $request) {
    $this->setCacheControlNoCache($response);
    $this->setExpiresNoCache($response);

    // There is no point in sending along headers necessary for cache
    // revalidation, if caching by proxies and browsers is denied in the first
    // place. Therefore remove ETag, Last-Modified and Vary in that case.
    $response->setEtag(NULL);
    $response->setLastModified(NULL);
    $response->setVary(NULL);
  }

  /**
   * Add Cache-Control and Expires headers to a cacheable response.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   A response object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   */
  protected function setResponseCacheable(Response $response, Request $request) {
    // HTTP/1.0 proxies do not support the Vary header, so prevent any caching
    // by sending an Expires date in the past. HTTP/1.1 clients ignore the
    // Expires header if a Cache-Control: max-age directive is specified (see
    // RFC 2616, section 14.9.3).
    if (!$response->headers->has('Expires')) {
      $this->setExpiresNoCache($response);
    }

    $max_age = $this->config->get('cache.page.max_age');
    $response->headers->set('Cache-Control', 'public, max-age=' . $max_age);

    // In order to support HTTP cache-revalidation, ensure that there is a
    // Last-Modified and an ETag header on the response.
    if (!$response->headers->has('Last-Modified')) {
      $timestamp = REQUEST_TIME;
      $response->setLastModified(new \DateTime(gmdate(DateTimePlus::RFC7231, REQUEST_TIME)));
    }
    else {
      $timestamp = $response->getLastModified()->getTimestamp();
    }
    $response->setEtag($timestamp);

    // Allow HTTP proxies to cache pages for anonymous users without a session
    // cookie. The Vary header is used to indicates the set of request-header
    // fields that fully determines whether a cache is permitted to use the
    // response to reply to a subsequent request for a given URL without
    // revalidation.
    if (!$response->hasVary() && !Settings::get('omit_vary_cookie')) {
      $response->setVary('Cookie', FALSE);
    }
  }

  /**
   * Disable caching in the browser and for HTTP/1.1 proxies and clients.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   A response object.
   */
  protected function setCacheControlNoCache(Response $response) {
    $response->headers->set('Cache-Control', 'no-cache, must-revalidate, post-check=0, pre-check=0');
  }

  /**
   * Disable caching in ancient browsers and for HTTP/1.0 proxies and clients.
   *
   * HTTP/1.0 proxies do not support the Vary header, so prevent any caching by
   * sending an Expires date in the past. HTTP/1.1 clients ignore the Expires
   * header if a Cache-Control: max-age= directive is specified (see RFC 2616,
   * section 14.9.3).
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   A response object.
   */
  protected function setExpiresNoCache(Response $response) {
    $response->setExpires(\DateTime::createFromFormat('j-M-Y H:i:s T', '19-Nov-1978 05:00:00 UTC'));
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onRespond');
    return $events;
  }
}
