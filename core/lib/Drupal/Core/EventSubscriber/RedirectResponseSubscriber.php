<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\RedirectResponseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
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
   */
  public function __construct(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
  }

  /**
   * Allows manipulation of the response object when performing a redirect.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Event to process.
   */
  public function checkRedirectUrl(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceOf RedirectResponse) {
      $options = array();

      $destination = $event->getRequest()->query->get('destination');
      // A destination from \Drupal::request()->query always overrides the
      // current RedirectResponse. We do not allow absolute URLs to be passed
      // via \Drupal::request()->query, as this can be an attack vector, with
      // the following exception:
      // - Absolute URLs that point to this site (i.e. same base URL and
      //   base path) are allowed.
      if ($destination && (!UrlHelper::isExternal($destination) || UrlHelper::externalIsLocal($destination, base_path()))) {
        $destination = UrlHelper::parse($destination);

        $path = $destination['path'];
        $options['query'] = $destination['query'];
        $options['fragment'] = $destination['fragment'];
        // The 'Location' HTTP header must always be absolute.
        $options['absolute'] = TRUE;

        $response->setTargetUrl($this->urlGenerator->generateFromPath($path, $options));
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
