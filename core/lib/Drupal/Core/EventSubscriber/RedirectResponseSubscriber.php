<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\RedirectResponseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Allows manipulation of the response object when performing a redirect.
 */
class RedirectResponseSubscriber implements EventSubscriberInterface {

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a RedirectResponseSubscriber object.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(UrlGeneratorInterface $url_generator, RequestContext $request_context) {
    $this->urlGenerator = $url_generator;
    $this->requestContext = $request_context;
  }

  /**
   * Allows manipulation of the response object when performing a redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Event to process.
   */
  public function checkRedirectUrl(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceOf RedirectResponse) {
      $options = array();

      $request = $event->getRequest();
      $destination = $request->query->get('destination');
      // A destination from \Drupal::request()->query always overrides the
      // current RedirectResponse. We do not allow absolute URLs to be passed
      // via \Drupal::request()->query, as this can be an attack vector, with
      // the following exception:
      // - Absolute URLs that point to this site (i.e. same base URL and
      //   base path) are allowed.
      if ($destination) {
        if (!UrlHelper::isExternal($destination)) {
          // The destination query parameter can be a relative URL in the sense
          // of not including the scheme and host, but its path is expected to
          // be absolute (start with a '/'). For such a case, prepend the
          // scheme and host, because the 'Location' header must be absolute.
          if (strpos($destination, '/') === 0) {
            $destination = $request->getSchemeAndHttpHost() . $destination;
          }
          else {
            // Legacy destination query parameters can be relative paths that
            // have not yet been converted to URLs (outbound path processors
            // and other URL handling still needs to be performed).
            // @todo As generateFromPath() is deprecated, remove this in
            //   https://www.drupal.org/node/2418219.
            $destination = UrlHelper::parse($destination);
            $path = $destination['path'];
            $options['query'] = $destination['query'];
            $options['fragment'] = $destination['fragment'];
            // The 'Location' HTTP header must always be absolute.
            $options['absolute'] = TRUE;
            $destination = $this->urlGenerator->generateFromPath($path, $options);
          }
          $response->setTargetUrl($destination);
        }
        elseif (UrlHelper::externalIsLocal($destination, $this->requestContext->getCompleteBaseUrl())) {
          $response->setTargetUrl($destination);
        }
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('checkRedirectUrl');
    return $events;
  }
}
