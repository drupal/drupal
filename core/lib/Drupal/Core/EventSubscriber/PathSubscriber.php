<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\PathSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Access subscriber for controller requests.
 */
class PathSubscriber extends PathListenerBase implements EventSubscriberInterface {

  /**
   * Resolve the system path.
   *
   * @todo The path system should be objectified to remove the function calls in
   *   this method.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestPathResolve(GetResponseEvent $event) {
    $request = $event->getRequest();

    $path = $this->extractPath($request);

    $path = drupal_get_normal_path($path);

    $this->setPath($request, $path);
  }

  /**
   * Resolve the front-page default path.
   *
   * @todo The path system should be objectified to remove the function calls in
   *   this method.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestFrontPageResolve(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = $this->extractPath($request);

    if (empty($path)) {
      // @todo Temporary hack. Fix when configuration is injectable.
      $path = config('system.site')->get('page.front');
      if (empty($path)) {
        $path = 'user';
      }
    }

    $this->setPath($request, $path);
  }

  /**
   * Decode language information embedded in the request path.
   *
   * @todo Refactor this entire method to inline the relevant portions of
   *   drupal_language_initialize(). See the inline comment for more details.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestLanguageResolve(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = $this->extractPath($request);

    // drupal_language_initialize() combines:
    // - Determination of language from $request information (e.g., path).
    // - Determination of language from other information (e.g., site default).
    // - Population of determined language into drupal_container().
    // - Removal of language code from _current_path().
    // @todo Decouple the above, but for now, invoke it and update the path
    //   prior to front page and alias resolution. When above is decoupled, also
    //   add 'langcode' (determined from $request only) to $request->attributes.
    _current_path($path);
    drupal_language_initialize();
    $path = _current_path();

    $this->setPath($request, $path);
  }

  /**
   * Decodes the path of the request.
   *
   * Parameters in the URL sometimes represent code-meaningful strings. It is
   * therefore useful to always urldecode() those values so that individual
   * controllers need not concern themselves with it. This is Drupal-specific
   * logic and may not be familiar for developers used to other Symfony-family
   * projects.
   *
   * @todo Revisit whether or not this logic is appropriate for here or if
   *   controllers should be required to implement this logic themselves. If we
   *   decide to keep this code, remove this TODO.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestDecodePath(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = $this->extractPath($request);

    $path = urldecode($path);

    $this->setPath($request, $path);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDecodePath', 200);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestLanguageResolve', 150);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestFrontPageResolve', 101);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestPathResolve', 100);

    return $events;
  }
}
