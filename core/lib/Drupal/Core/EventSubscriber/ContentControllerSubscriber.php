<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ContentControllerSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\ContentNegotiation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the request format onto the request object.
 *
 * @todo Remove this event subscriber after
 *   https://www.drupal.org/node/2092647 has landed.
 */
class ContentControllerSubscriber implements EventSubscriberInterface {

  /**
   * Content negotiation library.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $negotiation;

  /**
   * Constructs a new ContentControllerSubscriber object.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The Content Negotiation service.
   */
  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  /**
   * Sets the derived request format on the request.
   *
   * @todo Remove when https://www.drupal.org/node/2331919 lands.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequestDeriveFormat(GetResponseEvent $event) {
    $request = $event->getRequest();

    if (!$request->attributes->get('_format')) {
      $request->setRequestFormat($this->negotiation->getContentType($request));
    }
  }

  /**
   * Sets the _controller on a request when a _form is defined.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequestDeriveFormWrapper(GetResponseEvent $event) {
    $request = $event->getRequest();

    if ($request->attributes->has('_form')) {
      $request->attributes->set('_controller', 'controller.form:getContentResult');
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequestDeriveFormat', 31);
    $events[KernelEvents::REQUEST][] = array('onRequestDeriveFormWrapper', 29);

    return $events;
  }

}
