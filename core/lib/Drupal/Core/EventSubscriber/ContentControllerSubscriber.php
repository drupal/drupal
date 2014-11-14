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
 * Defines a subscriber to negotiate a _controller to use for a _content route.
 *
 * @todo Remove this event subscriber after both
 *   https://www.drupal.org/node/2092647 and https://www.drupal.org/node/2331919
 *   have landed.
 *
 * @see \Drupal\Core\EventSubscriber\MainContentViewSubscriber
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
   * Sets _content (if it exists) as the _controller.
   *
   * @todo Remove when https://www.drupal.org/node/2092647 lands.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequestDeriveController(GetResponseEvent $event) {
    $request = $event->getRequest();

    $controller = $request->attributes->get('_controller');
    $content = $request->attributes->get('_content');
    if (empty($controller) && !empty($content)) {
      $request->attributes->set('_controller',  $content);
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
    $events[KernelEvents::REQUEST][] = array('onRequestDeriveController', 30);

    return $events;
  }

}
