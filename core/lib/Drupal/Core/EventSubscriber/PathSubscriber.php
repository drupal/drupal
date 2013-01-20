<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\PathSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\CacheDecorator\AliasManagerCacheDecorator;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Access subscriber for controller requests.
 */
class PathSubscriber extends PathListenerBase implements EventSubscriberInterface {

  protected $aliasManager;

  public function __construct(AliasManagerCacheDecorator $alias_manager) {
    $this->aliasManager = $alias_manager;
  }

  /**
   * Resolve the system path.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestPathResolve(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = $this->extractPath($request);
    $path = $this->aliasManager->getSystemPath($path);
    $this->setPath($request, $path);
    // If this is the master request, set the cache key for the caching of all
    // system paths looked up during the request.
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      $this->aliasManager->setCacheKey($path);
    }
  }

  /**
   * Ensures system paths for the request get cached.
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    $this->aliasManager->writeCache();
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
    // drupal_language_initialize() combines:
    // - Determination of language from $request information (e.g., path).
    // - Determination of language from other information (e.g., site default).
    // - Population of determined language into drupal_container().
    // - Removal of language code from _current_path().
    // @todo Decouple the above, but for now, invoke it and update the path
    //   prior to front page and alias resolution. When above is decoupled, also
    //   add 'langcode' (determined from $request only) to $request->attributes.
    drupal_language_initialize();
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
    $events[KernelEvents::TERMINATE][] = array('onKernelTerminate', 200);

    return $events;
  }
}
