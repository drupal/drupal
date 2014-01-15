<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\FinishResponseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to handle finished responses.
 */
class FinishResponseSubscriber implements EventSubscriberInterface {

  /**
   * The LanguageManager object for retrieving the correct language code.
   *
   * @var LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a FinishResponseSubscriber object.
   *
   * @param LanguageManager $language_manager
   *  The LanguageManager object for retrieving the correct language code.
   */
  public function __construct(LanguageManager $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Sets extra headers on successful responses.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    $request = $event->getRequest();
    $response = $event->getResponse();

    // Set the X-UA-Compatible HTTP header to force IE to use the most recent
    // rendering engine or use Chrome's frame rendering engine if available.
    $response->headers->set('X-UA-Compatible', 'IE=edge,chrome=1', FALSE);

    // Set the Content-language header.
    $response->headers->set('Content-language', $this->languageManager->getCurrentLanguage()->id);

    // Because pages are highly dynamic, set the last-modified time to now
    // since the page is in fact being regenerated right now.
    // @todo Remove this and use a more intelligent default so that HTTP
    // caching can function properly.
    $response->setLastModified(new \DateTime(gmdate(DATE_RFC1123, REQUEST_TIME)));

    // Also give each page a unique ETag. This will force clients to include
    // both an If-Modified-Since header and an If-None-Match header when doing
    // conditional requests for the page (required by RFC 2616, section 13.3.4),
    // making the validation more robust. This is a workaround for a bug in
    // Mozilla Firefox that is triggered when Drupal's caching is enabled and
    // the user accesses Drupal via an HTTP proxy (see
    // https://bugzilla.mozilla.org/show_bug.cgi?id=269303): When an
    // authenticated user requests a page, and then logs out and requests the
    // same page again, Firefox may send a conditional request based on the
    // page that was cached locally when the user was logged in. If this page
    // did not have an ETag header, the request only contains an
    // If-Modified-Since header. The date will be recent, because with
    // authenticated users the Last-Modified header always refers to the time
    // of the request. If the user accesses Drupal via a proxy server, and the
    // proxy already has a cached copy of the anonymous page with an older
    // Last-Modified date, the proxy may respond with 304 Not Modified, making
    // the client think that the anonymous and authenticated pageviews are
    // identical.
    // @todo Remove this line as no longer necessary per
    //   http://drupal.org/node/1573064
    $response->setEtag(REQUEST_TIME);

    // Authenticated users are always given a 'no-cache' header, and will fetch
    // a fresh page on every request. This prevents authenticated users from
    // seeing locally cached pages.
    // @todo Revisit whether or not this is still appropriate now that the
    //   Response object does its own cache control processing and we intend to
    //   use partial page caching more extensively.

    // Attach globally-declared headers to the response object so that Symfony
    // can send them for us correctly.
    // @todo remove this once we have removed all drupal_add_http_header() calls
    $headers = drupal_get_http_header();
    foreach ($headers as $name => $value) {
      $response->headers->set($name, $value, FALSE);
    }

    $max_age = \Drupal::config('system.performance')->get('cache.page.max_age');
    if ($max_age > 0 && ($cache = drupal_page_set_cache($response, $request))) {
      drupal_serve_page_from_cache($cache, $response, $request);
    }
    else {
      $response->setExpires(\DateTime::createFromFormat('j-M-Y H:i:s T', '19-Nov-1978 05:00:00 GMT'));
      $response->headers->set('Cache-Control', 'no-cache, must-revalidate, post-check=0, pre-check=0');
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onRespond');
    return $events;
  }
}
