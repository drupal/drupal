<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\RouteProcessorSubscriber.
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
use Drupal\Core\ContentNegotiation;

/**
 * Listener to process request controller information.
 */
class RouteProcessorSubscriber implements EventSubscriberInterface {

  protected $negotiation;

  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  /**
   * Sets a default controller for a route if one was not specified.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event that is created to create a response for a request.
   */
  public function onRequestSetController(GetResponseEvent $event) {
    $request = $event->getRequest();

    if (!$request->attributes->has('_controller') && $this->negotiation->getContentType($request) === 'html') {
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
