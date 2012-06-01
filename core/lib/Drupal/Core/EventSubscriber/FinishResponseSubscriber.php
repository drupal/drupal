<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\FinishResponseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to handle finished responses.
 */
class FinishResponseSubscriber implements EventSubscriberInterface {

  /**
   * Sets extra headers on successful responses.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();

    // Set the X-UA-Compatible HTTP header to force IE to use the most recent
    // rendering engine or use Chrome's frame rendering engine if available.
    $response->headers->set('X-UA-Compatible', 'IE=edge,chrome=1', false);

    // Set the Content-language header.
    $response->headers->set('Content-language', drupal_container()->get(LANGUAGE_TYPE_INTERFACE)->langcode);

    // Because pages are highly dynamic, set the last-modified time to now
    // since the page is in fact being regenerated right now.
    // @todo Remove this and use a more intelligent default so that HTTP
    // caching can function properly.
    $response->headers->set('Last-Modified', gmdate(DATE_RFC1123, REQUEST_TIME));

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
    $response->headers->set('ETag', '"' . REQUEST_TIME . '"');

    // Authenticated users are always given a 'no-cache' header, and will fetch
    // a fresh page on every request. This prevents authenticated users from
    // seeing locally cached pages.
    // @todo Revisit whether or not this is still appropriate now that the
    //   Response object does its own cache control procesisng and we intend to
    //   use partial page caching more extensively.
    $response->headers->set('Expires', 'Sun, 19 Nov 1978 05:00:00 GMT');
    $response->headers->set('Cache-Control', 'no-cache, must-revalidate, post-check=0, pre-check=0');
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
