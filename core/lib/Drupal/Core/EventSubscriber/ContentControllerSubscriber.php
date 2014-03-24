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
 * Defines a subscriber for setting the format of the request.
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
   * Associative array of supported mime types and their appropriate controller.
   *
   * @var array
   */
  protected $types = array(
    'drupal_dialog' => 'controller.dialog:dialog',
    'drupal_modal' => 'controller.dialog:modal',
    'html' => 'controller.page:content',
    'drupal_ajax' => 'controller.ajax:content',
  );

  /**
   * Sets the derived request format on the request.
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
   * Sets the _controller on a request based on the request format.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequestDeriveContentWrapper(GetResponseEvent $event) {
    $request = $event->getRequest();

    $controller = $request->attributes->get('_controller');
    if (empty($controller) && ($type = $request->getRequestFormat())) {
      if (isset($this->types[$type])) {
        $request->attributes->set('_controller', $this->types[$type]);
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
    $events[KernelEvents::REQUEST][] = array('onRequestDeriveFormat', 31);
    $events[KernelEvents::REQUEST][] = array('onRequestDeriveContentWrapper', 30);

    return $events;
  }

}
