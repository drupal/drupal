<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\RouterListener.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Drupal-specific Router listener.
 *
 * This is the bridge from the kernel to the UrlMatcher.
 */
class RouteProcessorSubscriber implements EventSubscriberInterface {

  /**
   * The Matcher object for this listener.
   *
   * This property is private in the base class, so we have to hack around it.
   *
   * @var Symfony\Component\Router\Matcher\UrlMatcherInterface
   */
  protected $urlMatcher;

  /**
   * The Logging object for this listener.
   *
   * This property is private in the base class, so we have to hack around it.
   *
   * @var Symfony\Component\HttpKernel\Log\LoggerInterface
   */
  protected $logger;

  public function __construct() {
  }

  /**
   * Sets a default controller for a route if one was not specified.
   */
  public function onRequestSetController(GetResponseEvent $event) {
    $request = $event->getRequest();

    if (!$request->attributes->has('_controller') && $request->attributes->has('_content')) {
      $request->attributes->set('_controller', '\Drupal\Core\HtmlPageController::content');
    }

  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    // The RouterListener has priority 32, and we need to run after that.
    $events[KernelEvents::REQUEST][] = array('onRequestSetController', 30);

    return $events;
  }
}
